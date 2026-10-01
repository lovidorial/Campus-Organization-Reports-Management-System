<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\ActivityReportPhoto;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityReportReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_early_activity_report_is_blocked_on_form_and_submission(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        $user = User::factory()->create(['role' => 'user']);
        [, , $activityRequest] = $this->activityFixture($user, '2026-09-30', '2026-10-02');

        $this->actingAs($user)
            ->from(route('activity-monitor.index'))
            ->get(route('activity-reports.create', $activityRequest))
            ->assertRedirect(route('activity-monitor.index'))
            ->assertSessionHasErrors('activity_date');

        $this->actingAs($user)
            ->from(route('activity-monitor.index'))
            ->post(route('activity-reports.store', $activityRequest), $this->reportPayload([
                'photos' => [UploadedFile::fake()->image('proof.jpg')],
            ]))
            ->assertRedirect(route('activity-monitor.index'))
            ->assertSessionHasErrors('activity_date');

        $this->assertDatabaseCount('activity_reports', 0);
    }

    public function test_activity_monitor_disables_report_action_until_the_end_date(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        $user = User::factory()->create([
            'role' => 'user',
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);
        $this->activityFixture($user, '2026-09-30', '2026-10-02');

        $this->actingAs($user)
            ->get(route('activity-monitor.index'))
            ->assertOk()
            ->assertSee('Report unavailable')
            ->assertSee('Available after the activity end date');
    }

    public function test_initial_report_requires_at_least_one_photo(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        [, , $activityRequest] = $this->activityFixture($user, '2026-09-30');

        $this->actingAs($user)
            ->post(route('activity-reports.store', $activityRequest), $this->reportPayload())
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('activity_reports', 0);
    }

    public function test_admin_can_approve_report_and_notify_organization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        [, $plannedActivity, $activityRequest] = $this->activityFixture($user, '2026-09-30');
        $report = $this->createReport($activityRequest, ['feedback' => 'Previously marked.']);

        $this->actingAs($admin)
            ->post(route('admin.reports.approve', $report))
            ->assertRedirect(route('admin.activities', absolute: false));

        $this->assertDatabaseHas('activity_reports', [
            'id' => $report->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'feedback' => null,
        ]);
        $this->assertNotNull($report->fresh()->reviewed_at);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'activity_report.approved',
            'causer_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'activity_report_approved',
        ]);
        $this->assertSame('Completed', $plannedActivity->fresh()->monitoringStatus()['status']);
    }

    public function test_admin_can_request_revision_with_feedback_and_notify_organization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        [, , $activityRequest] = $this->activityFixture($user, '2026-09-30');
        $report = $this->createReport($activityRequest);

        $this->actingAs($admin)
            ->from(route('admin.activities'))
            ->post(route('admin.reports.request-revision', $report), ['feedback' => ''])
            ->assertSessionHasErrors('feedback');

        $feedback = 'Please include a clearer participant count.';
        $this->actingAs($admin)
            ->post(route('admin.reports.request-revision', $report), ['feedback' => $feedback])
            ->assertRedirect(route('admin.activities', absolute: false));

        $this->assertDatabaseHas('activity_reports', [
            'id' => $report->id,
            'status' => 'needs_revision',
            'feedback' => $feedback,
            'reviewed_by' => $admin->id,
        ]);
        $this->assertNotNull($report->fresh()->reviewed_at);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'activity_report.revision_requested',
            'causer_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'activity_report_revision_requested',
            'message' => $feedback,
        ]);
    }

    public function test_organization_resubmission_resets_review_and_keeps_existing_photos(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        [, , $activityRequest] = $this->activityFixture($user, '2026-09-30');
        $report = $this->createReport($activityRequest, [
            'status' => 'needs_revision',
            'feedback' => 'Add the attendance documentation.',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->create(['role' => 'admin'])->id,
        ]);
        $photoPath = 'uploads/activity-photos/previous-proof.jpg';
        Storage::disk('public')->put($photoPath, 'previous proof');
        ActivityReportPhoto::create([
            'activity_report_id' => $report->id,
            'path' => $photoPath,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('activity-reports.store', $activityRequest), $this->reportPayload([
                'attendance_sheet' => UploadedFile::fake()->create('attendance.pdf', 120, 'application/pdf'),
            ]))
            ->assertRedirect(route('activity-requests.show', $activityRequest));

        $report->refresh();
        $this->assertSame('pending', $report->status);
        $this->assertNull($report->feedback);
        $this->assertNull($report->reviewed_at);
        $this->assertNull($report->reviewed_by);
        $this->assertSame(1, $report->photos()->count());
        $this->assertNotNull($report->attendance_sheet_path);
        Storage::disk('private')->assertExists($report->attendance_sheet_path);
    }

    private function activityFixture(User $user, string $date, ?string $endDate = null): array
    {
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $plannedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Community Workshop',
            'date' => $date,
            'end_date' => $endDate,
            'venue' => 'Main Hall',
            'category' => 'Academic',
        ]);
        $activityRequest = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $plannedActivity->id,
            'title' => $plannedActivity->title,
            'date' => $date,
            'end_date' => $endDate,
            'venue' => $plannedActivity->venue,
            'category' => $plannedActivity->category,
            'communication_letter' => 'letters/approved.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $plannedActivity->update(['activity_request_id' => $activityRequest->id]);

        return [$gpoa, $plannedActivity, $activityRequest];
    }

    private function createReport(ActivityRequest $activityRequest, array $attributes = []): ActivityReport
    {
        return ActivityReport::create(array_merge([
            'activity_request_id' => $activityRequest->id,
            'narrative_report' => 'activity-documents/narrative-reports/report.pdf',
            'narrative_source' => 'generated',
            'narrative_content' => ['body' => 'The event took place.'],
            'submitted_at' => now(),
            'signed_by_secretary' => true,
            'signed_by_governor' => true,
            'signed_by_advisor' => true,
            'signed_by_dean_president' => true,
        ], $attributes));
    }

    private function reportPayload(array $attributes = []): array
    {
        return array_merge([
            'narrative_source' => 'generated',
            'narrative_content' => 'The activity was conducted as planned.',
            'signed_by_secretary' => '1',
            'signed_by_governor' => '1',
            'signed_by_advisor' => '1',
            'signed_by_dean_president' => '1',
        ], $attributes);
    }
}