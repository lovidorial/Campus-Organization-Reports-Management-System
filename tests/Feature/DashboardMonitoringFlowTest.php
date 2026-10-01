<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMonitoringFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_monitoring_status_instead_of_approval_workflow(): void
    {
        $user = User::factory()->create([
            'term' => '1st Semester',
            'school_year' => '2025-2026',
        ]);

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'college' => 'CICS',
            'status' => 'pending',
        ]);

        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Activity 1',
            'date' => '2025-11-10',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => 'Activity 1',
            'date' => '2025-11-10',
            'venue' => 'Main Hall',
            'category' => 'Education',
            'communication_letter' => 'letters/letter.pdf',
            'status' => 'pending',
        ]);

        $activity->update(['activity_request_id' => $request->id]);

        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/report.pdf',
            'submitted_at' => now(),
            'narrative_source' => 'uploaded',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('GPOA submitted');
        $response->assertSee('Monitoring Progress');
        $response->assertSee('Ongoing');
        $response->assertSee('Open Activity Monitor');
        $response->assertDontSee('Summary Report');
        $response->assertDontSee('Submit report');
        $response->assertDontSee('Awaiting GPOA approval');
        $response->assertDontSee('GPOA approved');
    }
}
