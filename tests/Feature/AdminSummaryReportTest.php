<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\MonitoringResult;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_includes_archived_activities_in_progress_and_term_sy_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create([
            'name' => 'CICS Student Council',
            'type' => 'Major Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'college' => 'CICS',
        ]);

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'submitted',
        ]);

        $completed = $gpoa->activities()->create([
            'title' => 'Completed Outreach',
            'date' => '2027-01-10',
            'venue' => 'Main Hall',
            'category' => 'Outreach',
            'estimated_budget' => 1200,
        ]);
        $archived = $gpoa->activities()->create([
            'title' => 'Archived Orientation',
            'date' => '2027-03-10',
            'venue' => 'Lecture Hall',
            'category' => 'Orientation',
            'estimated_budget' => 900,
            'archived_at' => now(),
        ]);
        $pending = $gpoa->activities()->create([
            'title' => 'Pending Symposium',
            'date' => '2027-05-10',
            'venue' => 'Auditorium',
            'category' => 'Symposium',
            'estimated_budget' => 800,
        ]);

        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $completed->id,
            'title' => $completed->title,
            'date' => $completed->date,
            'venue' => $completed->venue,
            'category' => $completed->category,
            'communication_letter' => 'letters/completed.pdf',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $completed->update(['activity_request_id' => $request->id]);
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/completed.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $page = $this->actingAs($admin)->get(route('admin.summary-report', [
            'organization' => $user->id,
            'college' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]));

        $page->assertOk();
        $page->assertSee('Term / SY');
        $page->assertSee('1st Term / 2026-2027');
        $page->assertSee('Archived Orientation');
        $page->assertSee('No (1 left)');
        $this->assertStringContainsString('1st Term / 2026-2027', $page->getContent());
    }

    public function test_summary_report_shows_narrative_content_link_and_late_status_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create(['name' => 'CICS Student Council', 'type' => 'Major Student Organization', 'college' => 'CICS', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'user', 'organization_id' => $organization->id, 'org_name' => $organization->name, 'college' => 'CICS']);
        $gpoa = Gpoa::create(['user_id' => $user->id, 'term' => '1st Term', 'school_year' => '2026-2027', 'college' => 'CICS', 'status' => 'submitted']);
        $activity = $gpoa->activities()->create(['title' => 'Late activity', 'date' => '2026-01-10', 'venue' => 'Hall', 'category' => 'Outreach', 'estimated_budget' => 200, 'archived_at' => null]);
        $request = ActivityRequest::create(['user_id' => $user->id, 'gpoa_id' => $gpoa->id, 'gpoa_activity_id' => $activity->id, 'title' => $activity->title, 'date' => $activity->date, 'venue' => $activity->venue, 'category' => $activity->category, 'communication_letter' => 'letters/report.pdf', 'status' => ActivityRequest::STATUS_PENDING]);
        $activity->update(['activity_request_id' => $request->id]);
        ActivityReport::create(['activity_request_id' => $request->id, 'narrative_content' => ['body' => 'Narrative text'], 'narrative_source' => 'generated', 'status' => 'pending', 'submitted_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.summary-report'));

        $response->assertOk();
        $response->assertSee('Late (overlaps with Pending/Ongoing)');
        $response->assertSee('View');
        $response->assertSee('href="' . route('admin.file.view', [$request->id, 'narrative']) . '"', false);

        $statusCardResponse = $this->actingAs($admin)->get(route('admin.summary-report', ['status' => 'Ongoing']));
        $statusCardResponse->assertOk();
        $statusCardResponse->assertSee('status=Ongoing', false);
    }

    public function test_report_tracks_assessment_and_pagination_summary_totals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create(['name' => 'Student Org', 'type' => 'Student Organization', 'college' => 'CICS', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'user', 'organization_id' => $organization->id, 'org_name' => $organization->name, 'college' => 'CICS']);
        $gpoa = Gpoa::create(['user_id' => $user->id, 'term' => '2nd Term', 'school_year' => '2026-2027', 'college' => 'CICS', 'status' => 'submitted']);

        for ($i = 1; $i <= 30; $i++) {
            $activity = $gpoa->activities()->create([
                'title' => "Activity {$i}",
                'date' => "2027-03-{$i}",
                'venue' => 'Hall',
                'category' => 'Outreach',
                'estimated_budget' => 100,
            ]);
            if ($i <= 28) {
                $request = ActivityRequest::create([
                    'user_id' => $user->id,
                    'gpoa_id' => $gpoa->id,
                    'gpoa_activity_id' => $activity->id,
                    'title' => $activity->title,
                    'date' => $activity->date,
                    'venue' => $activity->venue,
                    'category' => $activity->category,
                    'communication_letter' => 'letters/letter.pdf',
                    'status' => ActivityRequest::STATUS_PENDING,
                ]);
                $activity->update(['activity_request_id' => $request->id]);
                MonitoringResult::create([
                    'activity_request_id' => $request->id,
                    'gpoa_activity_id' => $activity->id,
                    'admin_id' => $admin->id,
                    'compliance_status' => $i % 2 === 0 ? 'partial' : 'aligned',
                    'recorded_at' => now(),
                ]);
            }
        }

        $response = $this->actingAs($admin)->get(route('admin.summary-report', ['assessment' => 'partial', 'page' => 2]));

        $response->assertOk();
        $response->assertSee('Partially Aligned');
        $response->assertSee('assessment=partial', false);
        $response->assertSee('page=2', false);
        $response->assertSee('>Total</th>', false);

        $paginatedResponse = $this->actingAs($admin)->get(route('admin.summary-report', [
            'sort' => 'date',
            'direction' => 'desc',
            'page' => 2,
        ]));
        $paginatedResponse->assertOk();
        $paginatedResponse->assertSee('Showing 26 to 30 of 30 activities');
        $paginatedResponse->assertSee('Activity 5');
        $paginatedResponse->assertSee('sort=date', false);
        $paginatedResponse->assertSee('direction=desc', false);
        preg_match('/<a id="generateExcelReport" href="([^"]+)"/', $paginatedResponse->getContent(), $excelLink);
        preg_match('/<a id="generatePdfReport" href="([^"]+)"/', $paginatedResponse->getContent(), $pdfLink);
        $this->assertNotEmpty($excelLink[1] ?? null);
        $this->assertNotEmpty($pdfLink[1] ?? null);
        parse_str((string) parse_url(html_entity_decode($excelLink[1]), PHP_URL_QUERY), $excelQuery);
        parse_str((string) parse_url(html_entity_decode($pdfLink[1]), PHP_URL_QUERY), $pdfQuery);
        $this->assertArrayNotHasKey('page', $excelQuery);
        $this->assertArrayNotHasKey('page', $pdfQuery);

        $exportResponse = $this->actingAs($admin)->get(route('admin.summary-report.download', ['assessment' => 'partial', 'page' => 2]));
        $exportResponse->assertOk();
        $temporaryFile = tempnam(sys_get_temp_dir(), 'summary-report-');
        file_put_contents($temporaryFile, $exportResponse->streamedContent());

        try {
            $workbook = IOFactory::createReader('Xlsx')->load($temporaryFile);
            $this->assertStringContainsString('Assessment=partial', $workbook->getSheetByName('Activities')->getCell('A1')->getValue());
            $this->assertSame(16, $workbook->getSheetByName('Activities')->getHighestRow());
        } finally {
            unlink($temporaryFile);
        }
    }
}
