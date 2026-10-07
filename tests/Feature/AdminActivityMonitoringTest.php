<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminActivityMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_monitor_lists_gpoa_activities_with_derived_statuses_and_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);

        $notStarted = $this->plannedActivity($gpoa, 'No Documents', '2026-09-01');
        $ongoing = $this->plannedActivity($gpoa, 'Letter Only', '2026-10-10');
        $completed = $this->plannedActivity($gpoa, 'Both Documents', '2026-11-10');
        $otherUser = User::factory()->create(['org_name' => 'CET Society']);
        $otherGpoa = Gpoa::create([
            'user_id' => $otherUser->id,
            'term' => '2nd Term',
            'school_year' => '2027-2028',
            'college' => 'CET',
            'status' => 'pending',
        ]);
        $otherTermActivity = $this->plannedActivity($otherGpoa, 'Other Term Activity', '2027-01-10');
        $otherTermActivity->update(['category' => 'Community']);

        $letterRequest = $this->createRequest($user, $gpoa, $ongoing, 'letters/letter.pdf');
        $ongoing->update(['activity_request_id' => $letterRequest->id]);

        $completeRequest = $this->createRequest($user, $gpoa, $completed, 'letters/complete.pdf');
        $completed->update(['activity_request_id' => $completeRequest->id]);
        ActivityReport::create([
            'activity_request_id' => $completeRequest->id,
            'narrative_report' => 'reports/complete.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $page = $this->actingAs($admin)->get(route('admin.activities'));
        $page->assertOk()
            ->assertSee('Organization progress')
            ->assertSee('Pending')
            ->assertSee('Ongoing')
            ->assertSee('Completed')
            ->assertSee('Late')
            ->assertDontSee('No Documents')
            ->assertSee('Letter Only')
            ->assertSee('Both Documents')
            ->assertDontSee('Awaiting Resubmission')
            ->assertDontSee('Review Report')
            ->assertSee('View');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['tab' => 'todo']))
            ->assertOk()
            ->assertSee('No Documents')
            ->assertDontSee('Letter Only');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['status' => 'Completed']))
            ->assertOk()
            ->assertSee('Both Documents')
            ->assertDontSee('No Documents')
            ->assertDontSee('Letter Only');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['status' => 'Late']))
            ->assertOk()
            ->assertSee('No recent submissions match these filters.')
            ->assertDontSee('Both Documents');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['college' => 'CET', 'term' => '2nd Term', 'school_year' => '2027-2028']))
            ->assertOk()
            ->assertSee('No recent submissions match these filters.')
            ->assertDontSee('No Documents');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['organization' => $user->id, 'category' => 'Academic']))
            ->assertOk()
            ->assertSee('Organization progress')
            ->assertSee('Both Documents')
            ->assertDontSee('Other Term Activity')
            ->assertSee('33%');
    }

    public function test_admin_can_save_an_audited_remark_for_a_planned_activity_without_a_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Remark Without Request', '2026-10-15');

        $reviewUrl = route('admin.activities');
        $this->actingAs($admin)->from($reviewUrl)->post(route('admin.monitoring.record', $activity->id), [
            'compliance_status' => 'partial',
            'compliance_notes' => 'Confirm the participant count during the next check.',
        ])->assertRedirect($reviewUrl);

        $this->assertDatabaseHas('monitoring_results', [
            'activity_request_id' => null,
            'gpoa_activity_id' => $activity->id,
            'admin_id' => $admin->id,
            'compliance_status' => 'partial',
            'compliance_notes' => 'Confirm the participant count during the next check.',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'monitoring.remark_recorded',
            'causer_id' => $admin->id,
        ]);
        $this->assertSame('Pending', $activity->fresh()->monitoringStatus()['status']);
    }

    public function test_admin_can_open_request_review_page_and_non_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Admin Review Page Activity', '2026-10-15');
        $request = $this->createRequest($owner, $gpoa, $activity, 'letters/review.pdf');
        $activity->update(['activity_request_id' => $request->id]);
        $report = ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/review.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
            'status' => 'pending',
        ]);
        $request->programFlows()->create(['time' => '9:00 AM', 'flow' => 'Opening', 'person_in_charge' => 'Chair', 'sort_order' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.activity-requests.show', [$request, 'back' => route('admin.activities', ['tab' => 'recent', 'status' => 'Pending'])]))
            ->assertOk()
            ->assertSee('Admin Review Page Activity')
            ->assertSee('CICS Student Council')
            ->assertSee('Program flow')
            ->assertSee('Opening')
            ->assertSee('admin/activities?tab=recent&amp;status=Pending', false)
            ->assertSee(route('admin.reports.approve', $report), false)
            ->assertSee(route('admin.reports.request-revision', $report), false)
            ->assertSee('data-file-viewer', false);

        $this->actingAs($owner)
            ->get(route('admin.activity-requests.show', $request))
            ->assertForbidden();
    }

    public function test_admin_can_open_letter_narrative_attendance_and_photo_evidence(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $gpoa = Gpoa::create(['user_id' => $owner->id, 'term' => '1st Term', 'school_year' => '2026-2027', 'college' => 'CICS', 'status' => 'approved']);
        $activity = $this->plannedActivity($gpoa, 'Evidence Review Activity', '2026-10-15');
        $request = $this->createRequest($owner, $gpoa, $activity, 'review/letter.pdf');
        $activity->update(['activity_request_id' => $request->id]);
        $report = ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'review/narrative.pdf',
            'attendance_sheet_path' => 'review/attendance.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
            'status' => 'pending',
        ]);
        $photo = $report->photos()->create(['path' => 'review/photo.png', 'sort_order' => 0]);
        foreach (['review/letter.pdf', 'review/narrative.pdf', 'review/attendance.pdf', 'review/photo.png'] as $path) {
            Storage::disk('private')->put($path, 'file contents');
        }

        $this->actingAs($admin)->get(route('admin.file.view', [$request->id, 'communication']))->assertOk()->assertHeader('Content-Disposition', 'inline; filename=letter.pdf');
        $this->get(route('admin.file.view', [$request->id, 'narrative']))->assertOk()->assertHeader('Content-Disposition', 'inline; filename=narrative.pdf');
        $this->get(route('admin.reports.evidence', [$report, 'attendance']))->assertOk()->assertHeader('Content-Disposition', 'inline; filename=attendance.pdf');
        $this->get(route('admin.reports.evidence', [$report, 'photo-' . $photo->id]))->assertOk()->assertHeader('Content-Disposition', 'inline; filename=photo.png');
    }

    public function test_admin_can_download_private_activity_documents_from_monitoring(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Private Documents', '2026-10-15');
        $request = $this->createRequest($user, $gpoa, $activity, 'activity-documents/letter.pdf');
        Storage::disk('private')->put($request->communication_letter, 'letter pdf');
        $activity->update(['activity_request_id' => $request->id]);

        $this->actingAs($admin)
            ->get(route('admin.file.download', [$request->id, 'communication']))
            ->assertOk()
            ->assertDownload('Communication-Letter-' . $request->id . '.pdf');
    }

    public function test_admin_document_preview_uses_inline_mime_headers_and_attendance_keeps_its_extension(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Modal Preview Activity', '2026-10-15');
        $request = $this->createRequest($user, $gpoa, $activity, 'letters/letter.txt');
        $activity->update(['activity_request_id' => $request->id]);
        Storage::disk('private')->put('letters/letter.txt', 'letter contents');
        Storage::disk('private')->put('reports/attendance.csv', 'name,signature');
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/narrative.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'pending',
            'submitted_at' => now(),
            'attendance_sheet_path' => 'reports/attendance.csv',
        ]);

        $preview = $this->actingAs($admin)
            ->get(route('admin.file.view', [$request->id, 'communication']))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringStartsWith('text/plain', $preview->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $preview->headers->get('Content-Disposition'));
        $this->assertStringContainsString('letter.txt', $preview->headers->get('Content-Disposition'));

        $this->get(route('admin.file.download', [$request->id, 'attendance']))
            ->assertOk()
            ->assertDownload('Attendance-Sheet-' . $request->id . '.csv');

        $this->get(route('admin.activities'))
            ->assertOk()
            ->assertSee('data-file-viewer', false)
            ->assertSee('Communication Letter – Modal Preview Activity', false)
            ->assertSee('Open full page')
            ->assertSee('Narrative report awaiting review')
            ->assertSee('Return for revision');
    }

    public function test_admin_dashboard_uses_monitoring_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create([
            'name' => 'CICS Student Council',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'org_name' => $organization->name,
            'organization_id' => $organization->id,
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Monitoring Dashboard Activity', '2026-10-15');
        $request = $this->createRequest($user, $gpoa, $activity, 'letters/dashboard.pdf');
        $activity->update(['activity_request_id' => $request->id]);
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/dashboard.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Monitoring Dashboard')
            ->assertSee('Active Organizations')
            ->assertSee('Pending')
            ->assertSee('Monitoring Dashboard Activity')
            ->assertSee('Organizations by Completed Activities')
            ->assertSee('Activities by Category')
            ->assertSee('Recent Document Submissions')
            ->assertSee('Communication Letter')
            ->assertSee('Narrative Report')
            ->assertDontSee('Pending GPOAs')
            ->assertDontSee('Recent Submissions');
    }

    public function test_completed_activity_archive_restore_is_policy_checked_and_filtered(): void
    {
        $organization = Organization::create([
            'name' => 'CICS Student Council',
            'type' => 'Major Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
        ]);
        $otherOrg = Organization::create([
            'name' => 'CET Society',
            'type' => 'Major Student Organization',
            'college' => 'CET',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'role' => 'user',
            'organization_id' => $otherOrg->id,
            'org_name' => $otherOrg->name,
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Archivable Completed Activity', '2026-09-01');
        $request = $this->createRequest($owner, $gpoa, $activity, 'letters/archive.pdf');
        $activity->update(['activity_request_id' => $request->id]);
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/archive.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->post(route('activities.archive', $activity))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('activities.archive', $activity))
            ->assertRedirect();

        $this->assertSame('Archived', $activity->fresh()->monitoringStatus()['status']);
        $this->assertFalse($activity->fresh()->monitoringStatus()['late']);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'monitoring.activity_archived',
            'causer_id' => $owner->id,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.activities'))
            ->assertOk()
            ->assertDontSee('Archivable Completed Activity');
        $this->get(route('admin.activities', ['status' => 'Archived']))
            ->assertOk()
            ->assertSee('Archivable Completed Activity')
            ->assertSee('Restore');

        $this->post(route('activities.restore', $activity))->assertRedirect();
        $this->assertNull($activity->fresh()->archived_at);
        $this->assertSame('Completed', $activity->fresh()->monitoringStatus()['status']);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'monitoring.activity_restored',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_only_completed_activities_can_be_archived(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Not Completed', '2026-09-01');

        $this->actingAs($owner)
            ->post(route('activities.archive', $activity))
            ->assertStatus(422);

        $this->assertNull($activity->fresh()->archived_at);
    }

    public function test_admin_recent_activity_is_ordered_by_latest_submission_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        foreach ([
            ['Oldest Submission', '2026-09-01', '2026-09-01 08:00:00'],
            ['Newest Submission', '2026-08-01', '2026-09-03 10:00:00'],
            ['Middle Submission', '2026-09-02', '2026-09-02 09:00:00'],
        ] as [$title, $date, $submittedAt]) {
            $activity = $this->plannedActivity($gpoa, $title, $date);
            $activityRequest = $this->createRequest($user, $gpoa, $activity, 'letters/' . str_replace(' ', '-', $title) . '.pdf');
            $activityRequest->update(['communication_letter_signed_at' => $submittedAt]);
            $activity->update(['activity_request_id' => $activityRequest->id]);
        }

        $this->actingAs($admin)
            ->get(route('admin.activities'))
            ->assertOk()
            ->assertSeeInOrder(['Newest Submission', 'Middle Submission', 'Oldest Submission']);
    }

    public function test_admin_not_yet_started_tab_excludes_submitted_activities(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $submitted = $this->plannedActivity($gpoa, 'Letter Already Submitted', '2026-09-01');
        $activityRequest = $this->createRequest($user, $gpoa, $submitted, 'letters/submitted.pdf');
        $activityRequest->update(['communication_letter_signed_at' => now()]);
        $submitted->update(['activity_request_id' => $activityRequest->id]);
        $this->plannedActivity($gpoa, 'No Documents Yet', '2026-10-01');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['tab' => 'todo']))
            ->assertOk()
            ->assertSee('No Documents Yet')
            ->assertDontSee('Letter Already Submitted');
    }

    public function test_admin_activity_pagination_preserves_active_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'Searchable Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '2nd Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        for ($number = 1; $number <= 11; $number++) {
            $activity = $this->plannedActivity($gpoa, "Needle Activity {$number}", today()->subDays($number)->toDateString());
            $activityRequest = $this->createRequest($user, $gpoa, $activity, "letters/needle-{$number}.pdf");
            $activityRequest->update(['communication_letter_signed_at' => now()->subMinutes($number)]);
            $activity->update(['activity_request_id' => $activityRequest->id]);
        }

        $this->actingAs($admin)
            ->get(route('admin.activities', ['search' => 'Needle', 'term' => '2nd Term', 'tab' => 'recent']))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertSee('search=Needle', false)
            ->assertSee('term=2nd', false);
    }

    private function plannedActivity(Gpoa $gpoa, string $title, string $date): GpoaActivity
    {
        return GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => $title,
            'date' => $date,
            'venue' => 'Main Hall',
            'category' => 'Academic',
        ]);
    }

    private function createRequest(User $user, Gpoa $gpoa, GpoaActivity $activity, string $letterPath): ActivityRequest
    {
        return ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => $activity->category,
            'communication_letter' => $letterPath,
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
    }
}
