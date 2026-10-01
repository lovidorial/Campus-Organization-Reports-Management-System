<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_activity_monitor_shows_current_gpoa_activities_without_approval_gate(): void
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
            'date' => '2025-10-15',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => 'Activity 1',
            'date' => '2025-10-15',
            'venue' => 'Main Hall',
            'category' => 'Education',
            'communication_letter' => 'letters/activity-1.pdf',
            'status' => 'pending',
        ]);

        $activity->update(['activity_request_id' => $request->id]);

        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/activity-1.pdf',
            'submitted_at' => now(),
            'narrative_source' => 'uploaded',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('activity-monitor.index'));

        $response->assertOk();
        $response->assertSee('Activity Monitor');
        $response->assertSee('Activity 1');
        $response->assertSee('Completed');
        $response->assertSee('100%');
        $response->assertDontSee('You must have an approved GPOA');
    }

    public function test_archived_activities_are_hidden_by_default_and_shown_by_status_filter(): void
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
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Archived Field Activity',
            'date' => '2025-10-15',
            'archived_at' => now(),
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $this->actingAs($user)
            ->get(route('activity-monitor.index'))
            ->assertOk()
            ->assertDontSee('Archived Field Activity')
            ->assertSee('Archived');

        $this->get(route('activity-monitor.index', ['status' => 'Archived']))
            ->assertOk()
            ->assertSee('Archived Field Activity');
    }
}
