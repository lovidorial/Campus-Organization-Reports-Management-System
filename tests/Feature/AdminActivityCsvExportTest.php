<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_csv_escapes_fields_and_neutralizes_spreadsheet_formulas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = User::factory()->create(['org_name' => '=HYPERLINK("https://example.com")']);
        ActivityRequest::create([
            'user_id' => $organization->id,
            'title' => "Title, with \"quotes\"\nand a second line",
            'venue' => '@SUM(A1:A2)',
            'date' => '2026-10-15',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.activities.export', ['format' => 'excel']));

        $response->assertOk();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response->getContent());
        rewind($stream);
        $headers = fgetcsv($stream, 0, ',', '"', '');
        $row = fgetcsv($stream, 0, ',', '"', '');
        fclose($stream);

        $this->assertSame(['ID', 'Title', 'Organization', 'Venue', 'Date', 'Status', 'GPOA'], $headers);
        $this->assertSame("Title, with \"quotes\"\nand a second line", $row[1]);
        $this->assertSame("'=HYPERLINK(\"https://example.com\")", $row[2]);
        $this->assertSame("'@SUM(A1:A2)", $row[3]);
    }
}