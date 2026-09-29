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

        $this->assertSame('Not Started', $status['status']);
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
            reportPath: 'reports/report.pdf'
        );

        $status = $activity->monitoringStatus();

        $this->assertSame('Completed', $status['status']);
        $this->assertFalse($status['late']);
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

    private function createActivity(
        string $date = '2026-09-29',
        ?string $letterPath = null,
        ?string $reportPath = null
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
            ]);
        }

        return $activity->fresh();
    }
}
