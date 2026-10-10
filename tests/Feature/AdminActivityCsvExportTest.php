<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminActivityCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_csv_has_machine_readable_headers_respects_filters_and_neutralizes_formulas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = User::factory()->create([
            'name' => 'Activity Submitter',
            'org_name' => '=HYPERLINK("https://example.com")',
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $organization->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'status' => 'pending',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => "Title, with \"quotes\"\nand a second line",
            'venue' => '@SUM(A1:A2)',
            'date' => '2026-10-15',
            'category' => 'Academic',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $organization->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => '2026-10-15',
            'end_date' => '2026-10-16',
            'venue' => $activity->venue,
            'communication_letter' => 'letters/letter.pdf',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $activity->update(['activity_request_id' => $request->id]);
        $gpoa->activities()->create([
            'title' => 'Unfiltered Activity',
            'date' => '2026-10-17',
            'category' => 'Sports',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.activities.export', ['format' => 'csv', 'category' => 'Academic']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response->getContent());
        rewind($stream);
        $headers = fgetcsv($stream, 0, ',', '"', '');
        $row = fgetcsv($stream, 0, ',', '"', '');
        $extraRow = fgetcsv($stream, 0, ',', '"', '');
        fclose($stream);

        $this->assertSame([
            'Activity ID', 'Activity Title', 'Organization / Office', 'Submitted By', 'Activity Venue', 'Start Date',
            'End Date', 'Monitoring Status', 'Late Submission', 'Communication Letter', 'Narrative Report', 'Academic Term', 'Academic Year',
        ], $headers);
        $this->assertSame("Title, with \"quotes\"\nand a second line", $row[1]);
        $this->assertSame("'=HYPERLINK(\"https://example.com\")", $row[2]);
        $this->assertSame('Activity Submitter', $row[3]);
        $this->assertSame("'@SUM(A1:A2)", $row[4]);
        $this->assertSame('2026-10-15', $row[5]);
        $this->assertSame('2026-10-16', $row[6]);
        $this->assertSame('Ongoing', $row[7]);
        $this->assertSame('Submitted', $row[9]);
        $this->assertFalse($extraRow);
    }

    public function test_activity_xlsx_includes_formatted_report_headers_counts_dates_and_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = User::factory()->create(['name' => 'CICS Submitter', 'org_name' => 'CICS-SC']);
        $gpoa = Gpoa::create([
            'user_id' => $organization->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'status' => 'pending',
        ]);
        $activity = $gpoa->activities()->create([
            'title' => 'Filtered Activity',
            'date' => '2026-10-15',
            'venue' => 'Main Hall',
            'category' => 'Academic',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $organization->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => '2026-10-15',
            'venue' => 'Main Hall',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $activity->update(['activity_request_id' => $request->id]);
        $gpoa->activities()->create(['title' => 'Excluded Activity', 'date' => '2026-10-16', 'category' => 'Sports']);

        $response = $this->actingAs($admin)->get(route('admin.activities.export', [
            'format' => 'xlsx',
            'category' => 'Academic',
            'school_year' => '2026-2027',
            'term' => '1st Term',
        ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertMatchesRegularExpression('/activities_report_\d{8}_\d{6}\.xlsx/', $response->headers->get('Content-Disposition'));

        $temporaryFile = tempnam(sys_get_temp_dir(), 'activity-report-');
        file_put_contents($temporaryFile, $response->streamedContent());

        try {
            $sheet = IOFactory::createReader('Xlsx')->load($temporaryFile)->getActiveSheet();
            $this->assertSame('OSDW — Activity Monitoring Report', $sheet->getCell('A1')->getValue());
            $this->assertSame('Academic Year: 2026-2027 | Term: 1st Term', $sheet->getCell('A2')->getValue());
            $this->assertSame('Pending', $sheet->getCell('A4')->getValue());
            $this->assertSame(1, $sheet->getCell('B4')->getValue());
            $this->assertSame('Activity ID', $sheet->getCell('A6')->getValue());
            $this->assertSame('Academic Year', $sheet->getCell('M6')->getValue());
            $this->assertSame('Filtered Activity', $sheet->getCell('B7')->getValue());
            $this->assertSame('Pending', $sheet->getCell('H7')->getValue());
            $this->assertTrue(is_numeric($sheet->getCell('F7')->getValue()));
            $this->assertSame('2026-10-15', ExcelDate::excelToDateTimeObject((float) $sheet->getCell('F7')->getValue())->format('Y-m-d'));
            $this->assertSame('mmm d, yyyy', $sheet->getStyle('F7')->getNumberFormat()->getFormatCode());
            $this->assertSame('A7', $sheet->getFreezePane());
            $this->assertSame('A6:M7', (string) $sheet->getAutoFilter()->getRange());
            $this->assertNotSame('Excluded Activity', $sheet->getCell('B7')->getValue());
        } finally {
            unlink($temporaryFile);
        }
    }

    public function test_only_admins_can_download_activity_exports(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        foreach (['csv', 'xlsx'] as $format) {
            $this->actingAs($user)
                ->get(route('admin.activities.export', ['format' => $format]))
                ->assertForbidden();
        }
    }

    public function test_unsupported_activity_export_format_returns_bad_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.activities.export', ['format' => 'pdf']))
            ->assertBadRequest();
    }
}