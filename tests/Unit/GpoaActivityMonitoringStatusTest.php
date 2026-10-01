<?php

namespace Tests\Unit;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\DocumentDeadline;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpoaActivityMonitoringStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_not_started_when_no_letter_or_report_exists(): void
    {
        $activity = $this->createActivity();

        $status = $activity->monitoringStatus();

        $this->assertSame('Pending', $status['status']);
        $this->assertTrue($status['late']);
    }

    public function test_it_reports_ongoing_when_only_one_document_exists(): void
    {
        $activity = $this->createActivity(letterPath: 'letters/letter.pdf');

        $status = $activity->monitoringStatus();

        $this->assertSame('Ongoing', $status['status']);
        $this->assertTrue($status['late']);
    }

    public function test_it_reports_completed_when_both_documents_exist(): void
    {
        $activity = $this->createActivity(
            letterPath: 'letters/letter.pdf',
            reportPath: 'reports/report.pdf',
            reportStatus: 'approved'
        );

        $status = $activity->monitoringStatus();

        $this->assertSame('Completed', $status['status']);
        $this->assertFalse($status['late']);
        $this->assertSame('Uploaded ![✔](https://static.xx.fbcdn.net/images/emoji.php/v9/t51/1/16/2714.png)', $activity->letterStatusLabel());
        $this->assertSame('Approved', $activity->narrativeStatusLabel());
    }

    public function test_it_reports_ongoing_until_a_submitted_report_is_approved(): void
    {
        $activity = $this->createActivity(
            letterPath: 'letters/letter.pdf',
            reportPath: 'reports/report.pdf'
        );

        $this->assertSame('Ongoing', $activity->monitoringStatus()['status']);
        $this->assertSame('For Review', $activity->narrativeStatusLabel());
    }

    public function test_archived_activity_is_archived_and_never_late(): void
    {
        $activity = $this->createActivity(date: '2024-01-15');
        $activity->forceFill(['archived_at' => now()])->save();

        $this->assertSame(['status' => 'Archived', 'late' => false], $activity->fresh()->monitoringStatus());
    }

    public function test_it_reports_pending_when_the_linked_request_is_rejected(): void
    {
        $activity = $this->createActivity(
            letterPath: 'letters/letter.pdf',
            reportPath: 'reports/report.pdf'
        );
        $activity->activityRequest()->update(['status' => 'rejected']);
        $activity->load('activityRequest.report');

        $status = $activity->monitoringStatus();

        $this->assertSame('Pending', $status['status']);
    }

    public function test_it_marks_activity_late_when_not_completed_after_deadline(): void
    {
        $activity = $this->createActivity(date: '2024-01-15', letterPath: 'letters/letter.pdf');

        DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REPORT,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'deadline_date' => '2024-01-20',
        ]);

        $status = $activity->monitoringStatus();

        $this->assertSame('Ongoing', $status['status']);
        $this->assertTrue($status['late']);
    }

    public function test_it_keeps_month_only_activity_from_being_late_until_the_last_day_of_its_month(): void
    {
        $activity = GpoaActivity::create([
            'gpoa_id' => Gpoa::create([
                'user_id' => User::factory()->create()->id,
                'term' => '1st Semester',
                'school_year' => '2025-2026',
                'college' => 'College of Arts and Sciences',
            ])->id,
            'title' => 'Month Activity',
            'date' => '2024-11-01',
            'date_is_month_only' => true,
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $this->travelTo('2024-11-20');
        $this->assertFalse($activity->monitoringStatus()['late']);

        $this->travelTo('2024-12-01');
        $this->assertTrue($activity->monitoringStatus()['late']);
    }

    private function createActivity(
        string $date = '2026-09-29',
        ?string $letterPath = null,
        ?string $reportPath = null,
        string $reportStatus = 'pending'
    ): GpoaActivity {
        $user = User::factory()->create();

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'college' => 'College of Arts and Sciences',
        ]);

        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Activity 1',
            'date' => $date,
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => $activity->category,
            'communication_letter' => $letterPath,
            'status' => 'pending',
        ]);

        $activity->update(['activity_request_id' => $request->id]);

        if ($reportPath) {
            ActivityReport::create([
                'activity_request_id' => $request->id,
                'narrative_report' => $reportPath,
                'submitted_at' => now(),
                'narrative_source' => 'uploaded',
                'status' => $reportStatus,
            ]);
        }

        return $activity->fresh();
    }
}
