<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_shows_approved_workflow_states_for_selected_month(): void
    {
        $user = User::factory()->create(['terms_accepted_at' => now()]);
        $venue = Venue::create(['name' => 'Main Hall']);

        $this->createActivity($user, $venue, 'Approved Meeting', '2026-10-12', 'approved');
        $this->createActivity($user, $venue, 'In Progress Event', '2026-10-13', 'in_progress');
        $this->createActivity($user, $venue, 'Closed Workshop', '2026-10-14', 'closed');
        $this->createActivity($user, $venue, 'Pending Request', '2026-10-15', 'pending');
        $this->createActivity($user, $venue, 'Rejected Request', '2026-10-16', 'rejected');
        $this->createActivity($user, $venue, 'November Event', '2026-11-02', 'approved');

        $this->actingAs($user)
            ->get(route('activities.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('href="' . route('activities.calendar') . '"', false)
            ->assertSee('Activity Calendar')
            ->assertSee('October 2026')
            ->assertSee('Approved Meeting')
            ->assertSee('In Progress Event')
            ->assertSee('Closed Workshop')
            ->assertSee('Rejected Request')
            ->assertSee('aria-label="Pending activity: Rejected Request"', false)
            ->assertDontSee('Pending Request')
            ->assertDontSee('November Event');
    }

    public function test_calendar_repeats_multi_day_activity_on_each_day_and_filters_by_venue(): void
    {
        $user = User::factory()->create(['terms_accepted_at' => now()]);
        $hall = Venue::create(['name' => 'Main Hall']);
        $court = Venue::create(['name' => 'Sports Court']);
        $multiDay = $this->createActivity($user, $hall, 'Three Day Event', '2026-10-30', 'approved', '2026-11-02');
        $this->createActivity($user, $court, 'Court Event', '2026-10-31', 'approved');

        $this->actingAs($user)
            ->get(route('activities.calendar', ['month' => '2026-10', 'venue' => $hall->id]))
            ->assertOk()
            ->assertSee('Three Day Event')
            ->assertDontSee('Court Event');

        $this->assertSame('2026-10-30', $multiDay->date->toDateString());
    }

    public function test_regular_user_calendar_does_not_show_other_users_activities(): void
    {
        $user = User::factory()->create(['terms_accepted_at' => now()]);
        $otherUser = User::factory()->create(['terms_accepted_at' => now()]);
        $venue = Venue::create(['name' => 'Main Hall']);
        $this->createActivity($user, $venue, 'My Activity', '2026-10-20', 'approved');
        $this->createActivity($otherUser, $venue, 'Other Organization Activity', '2026-10-21', 'approved');
        $myGpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'college' => 'CICS',
        ]);
        $otherGpoa = Gpoa::create([
            'user_id' => $otherUser->id,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'college' => 'CICS',
        ]);
        GpoaActivity::create([
            'id' => 1001,
            'gpoa_id' => $myGpoa->id,
            'title' => 'My Planned Activity',
            'date' => '2026-10-22',
            'venue' => $venue->name,
            'category' => 'Other',
        ]);
        GpoaActivity::create([
            'id' => 1002,
            'gpoa_id' => $otherGpoa->id,
            'title' => 'Other Planned Activity',
            'date' => '2026-10-23',
            'venue' => $venue->name,
            'category' => 'Other',
        ]);
        $this->assertSame(1, GpoaActivity::query()
            ->whereDate('date', '2026-10-22')
            ->whereHas('gpoa', fn ($query) => $query->where('user_id', $user->id))
            ->count());

        $this->actingAs($user)
            ->get(route('activities.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('My Activity')
            ->assertDontSee('Other Organization Activity')
            ->assertDontSee('Other Planned Activity');
    }

    public function test_admin_calendar_sidebar_links_to_calendar(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'terms_accepted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('activities.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('href="' . route('activities.calendar') . '"', false);
    }

    private function createActivity(
        User $user,
        Venue $venue,
        string $title,
        string $date,
        string $status,
        ?string $endDate = null
    ): ActivityRequest {
        return ActivityRequest::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'title' => $title,
            'category' => 'Other',
            'date' => $date,
            'end_date' => $endDate,
            'start_time' => '09:30',
            'venue' => $venue->name,
            'status' => $status,
        ]);
    }
}