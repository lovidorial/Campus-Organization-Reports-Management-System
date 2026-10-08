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

    protected function setUp(): void
    {
        parent::setUp();

        DocumentDeadline::query()->delete();
    }

    public function test_no_deadline_settings_means_a_past_activity_is_not_late(): void
    {
        $activity = $this->createActivity(date: '2024-01-15');

        $status = $activity->monitoringStatus();

        $this->assertSame('Pending', $status['status']);
        $this->assertFalse($status['late']);
    }

    public function test_it_reports_ongoing_when_only_one_document_exists(): void
    {
        $activity = $this->createActivity(letterPath: 'letters/letter.pdf');

        $status = $activity->monitoringStatus();

        $this->assertSame('Ongoing', $status['status']);
        $this->assertFalse($status['late']);
    }

    public function test_it_reports_completed_when_both_documents_exist(): void
    {
        $activity = $this->createActivity(
            letterPath: 'letters/letter.pdf',
            reportPath: 'reports/report.pdf',
            reportStatus: 'approved'
        );
        $this->createDeadline(deadlineDate: '2026-10-02');

        $status = $activity->monitoringStatus();

        $this->assertSame('Completed', $status['status']);
        $this->assertFalse($status['late']);
        $this->assertSame('Uploaded', $activity->letterStatusLabel());
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

    public function test_grace_period_expired_with_no_narrative_report_is_late(): void
    {
        $this->travelTo('2026-10-03 12:00:00');
        $activity = $this->createActivity(date: '2026-10-01', letterPath: 'letters/letter.pdf');
        $this->createDeadline(graceDays: 1);

        $status = $activity->monitoringStatus();

        $this->assertSame('Ongoing', $status['status']);
        $this->assertTrue($status['late']);
    }

    public function test_activity_within_grace_period_is_not_late(): void
    {
        $this->travelTo('2026-10-03 12:00:00');
        $activity = $this->createActivity(date: '2026-10-02', letterPath: 'letters/letter.pdf');
        $this->createDeadline(graceDays: 2);

        $this->assertFalse($activity->monitoringStatus()['late']);
    }

    public function test_term_cutoff_passed_with_no_narrative_report_is_late(): void
    {
        $this->travelTo('2026-10-03 12:00:00');
        $activity = $this->createActivity(date: '2026-10-15', letterPath: 'letters/letter.pdf');
        $this->createDeadline(deadlineDate: '2026-10-02');

        $this->assertTrue($activity->monitoringStatus()['late']);
    }

    public function test_submitted_narrative_reports_are_never_late(): void
    {
        $this->travelTo('2026-10-03 12:00:00');
        $this->createDeadline(graceDays: 0, deadlineDate: '2026-10-02');

        foreach (['pending', 'needs_revision'] as $reportStatus) {
            $activity = $this->createActivity(
                date: '2026-09-01',
                letterPath: 'letters/letter.pdf',
                reportPath: 'reports/'.$reportStatus.'.pdf',
                reportStatus: $reportStatus
            );

            $this->assertFalse($activity->monitoringStatus()['late']);
        }

        $contentOnlyActivity = $this->createActivity(date: '2026-09-01', letterPath: 'letters/content-only.pdf');
        $contentOnlyActivity->activityRequest->report()->create([
            'narrative_content' => ['body' => 'Submitted narrative'],
            'narrative_source' => 'generated',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->assertFalse($contentOnlyActivity->fresh()->monitoringStatus()['late']);
    }

    public function test_activity_request_deadline_does_not_mark_activity_late(): void
    {
        $this->travelTo('2026-10-03 12:00:00');
        $activity = $this->createActivity(date: '2026-10-15', letterPath: 'letters/letter.pdf');
        DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REQUEST,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'deadline_date' => '2026-10-02',
        ]);

        $this->assertFalse($activity->monitoringStatus()['late']);
    }

    public function test_grace_period_uses_month_only_and_date_range_end_dates(): void
    {
        $this->travelTo('2026-10-03 12:00:00');
        $monthOnly = $this->createActivity(date: '2026-09-01', letterPath: 'letters/month.pdf', monthOnly: true);
        $dateRange = $this->createActivity(date: '2026-09-29', letterPath: 'letters/range.pdf', endDate: '2026-10-01', timeFrame: 'date_range');
        $this->createDeadline(graceDays: 2);

        $this->assertTrue($monthOnly->monitoringStatus()['late']);
        $this->assertFalse($dateRange->monitoringStatus()['late']);

        $this->travelTo('2026-10-04 12:00:00');
        $this->assertTrue($monthOnly->fresh()->monitoringStatus()['late']);
        $this->assertTrue($dateRange->fresh()->monitoringStatus()['late']);
    }

    public function test_grace_period_without_an_activity_date_is_skipped(): void
    {
        $this->travelTo('2026-10-03 12:00:00');
        $activity = $this->createActivity(letterPath: 'letters/no-date.pdf');
        $activity->setAttribute('date', null);
        $this->createDeadline(graceDays: 0);

        $this->assertFalse($activity->monitoringStatus()['late']);
    }

    private function createActivity(
        ?string $date = '2026-09-29',
        ?string $letterPath = null,
        ?string $reportPath = null,
        string $reportStatus = 'pending',
        ?string $endDate = null,
        string $timeFrame = 'exact',
        bool $monthOnly = false
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
            'time_frame' => $timeFrame,
            'date' => $date,
            'end_date' => $endDate,
            'date_is_month_only' => $monthOnly,
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

    private function createDeadline(?int $graceDays = null, ?string $deadlineDate = null): DocumentDeadline
    {
        return DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REPORT,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'grace_days' => $graceDays,
            'deadline_date' => $deadlineDate,
        ]);
    }
}
