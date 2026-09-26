<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_aggregates_all_statuses_and_supports_excel_and_pdf_exports(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = User::factory()->create([
            'role' => 'user',
            'org_name' => 'CICS Student Council',
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $organization->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        foreach ([
            ['status' => ActivityRequest::STATUS_APPROVED, 'title' => 'Approved Outreach'],
            ['status' => ActivityRequest::STATUS_PENDING, 'title' => 'Pending Symposium'],
        ] as $index => $requestData) {
            ActivityRequest::create([
                'user_id' => $organization->id,
                'gpoa_id' => $gpoa->id,
                'title' => $requestData['title'],
                'date' => '2026-10-' . str_pad((string) (10 + $index), 2, '0', STR_PAD_LEFT),
                'venue' => 'Main Hall',
                'category' => $index === 0 ? 'Outreach' : 'Symposium',
                'participants_count' => 30 + $index,
                'estimated_budget' => 1000 + ($index * 500),
                'status' => $requestData['status'],
            ]);
        }

        $filters = ['term' => '1st Term'];
        $page = $this->actingAs($admin)->get(route('admin.summary-report', $filters));

        $page->assertOk();
        $page->assertSee('Organization Summary');
        $page->assertSee('CICS Student Council');
        $page->assertSee('Category Summary');
        $page->assertSee('Status Summary');
        $page->assertSee('Pending');

        $excel = $this->actingAs($admin)->get(route('admin.summary-report.download', $filters));
        $excel->assertOk();
        $this->assertStringContainsString('summary-report.xlsx', $excel->headers->get('content-disposition'));

        $pdf = $this->actingAs($admin)->get(route('admin.summary-report.pdf', $filters));
        $pdf->assertOk();
        $this->assertStringContainsString('summary-report.pdf', $pdf->headers->get('content-disposition'));
    }
}