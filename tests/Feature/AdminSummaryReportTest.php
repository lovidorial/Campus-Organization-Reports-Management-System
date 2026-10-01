<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_reports_completed_and_unrequested_pending_gpoa_activities(): void
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
        $completedActivity = $gpoa->activities()->create([
            'title' => 'Completed Outreach',
            'date' => '2027-01-10',
            'venue' => 'Main Hall',
            'category' => 'Outreach',
            'estimated_budget' => 1200,
        ]);
        $pendingActivity = $gpoa->activities()->create([
            'title' => 'Unrequested Symposium',
            'date' => '2027-02-10',
            'venue' => 'Auditorium',
            'category' => 'Symposium',
            'estimated_budget' => 800,
        ]);

        $activityRequest = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $completedActivity->id,
            'title' => $completedActivity->title,
            'date' => $completedActivity->date,
            'venue' => $completedActivity->venue,
            'category' => $completedActivity->category,
            'communication_letter' => 'letters/completed.pdf',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $completedActivity->update(['activity_request_id' => $activityRequest->id]);
        ActivityReport::create([
            'activity_request_id' => $activityRequest->id,
            'narrative_report' => 'reports/completed.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $filters = [
            'organization' => $user->id,
            'college' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'date_from' => '2027-01-01',
            'date_to' => '2027-12-31',
        ];
        $page = $this->actingAs($admin)->get(route('admin.summary-report', $filters));

        $page->assertOk()
            ->assertSee('Organization Summary')
            ->assertSee('CICS Student Council')
            ->assertSee('Category Summary')
            ->assertSee('Monitoring Status Summary')
            ->assertSee('Completed Outreach')
            ->assertSee('Unrequested Symposium')
            ->assertSee('Activity #1')
            ->assertSee('Activity #2')
            ->assertSee('Completed')
            ->assertSee('Pending')
            ->assertSee('Uploaded')
            ->assertSee('Approved')
            ->assertSee('Total Activities')
            ->assertSee('Progress');

        $filteredPage = $this->actingAs($admin)->get(route('admin.summary-report', array_merge($filters, ['status' => 'Pending'])));
        $filteredPage->assertOk()
            ->assertSee('Unrequested Symposium')
            ->assertDontSee('Completed Outreach');

        $excel = $this->actingAs($admin)->get(route('admin.summary-report.download', $filters));
        $excel->assertOk();
        $this->assertStringContainsString('summary-report.xlsx', $excel->headers->get('content-disposition'));

        $pdf = $this->actingAs($admin)->get(route('admin.summary-report.pdf', $filters));
        $pdf->assertOk();
        $this->assertStringContainsString('summary-report.pdf', $pdf->headers->get('content-disposition'));
    }
}