<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_csv_escapes_fields_and_neutralizes_spreadsheet_formulas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = User::factory()->create([
            'name' => 'Archived Submitter Name',
            'org_name' => '=HYPERLINK("https://example.com")',
            'officer_status' => 'archived',
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $organization->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'status' => 'pending',
        ]);
        GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'user_id' => $organization->id,
            'title' => "Title, with \"quotes\"\nand a second line",
            'venue' => '@SUM(A1:A2)',
            'date' => '2026-10-15',
            'category' => 'Academic',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.activities.export', ['format' => 'excel']));

        $response->assertOk();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response->getContent());
        rewind($stream);
        $headers = fgetcsv($stream, 0, ',', '"', '');
        $row = fgetcsv($stream, 0, ',', '"', '');
        fclose($stream);

        $this->assertSame([
            'Activity ID', 'Title', 'Organization', 'Submitted By', 'Venue', 'Date', 'Monitoring Status',
            'Late', 'Communication Letter', 'Narrative Report', 'Term', 'School Year',
        ], $headers);
        $this->assertSame("Title, with \"quotes\"\nand a second line", $row[1]);
        $this->assertSame("'=HYPERLINK(\"https://example.com\")", $row[2]);
        $this->assertSame('Archived Submitter Name', $row[3]);
        $this->assertSame("'@SUM(A1:A2)", $row[4]);
        $this->assertSame('Pending', $row[6]);
    }
}