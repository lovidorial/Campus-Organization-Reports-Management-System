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

        $response = $this->actingAs($user)
            ->get(route('activities.calendar', ['month' => '2026-10', 'venue' => $hall->id]))
            ->assertOk()
            ->assertSee('Three Day Event')
            ->assertDontSee('Court Event');

        $this->assertSame(2, substr_count($response->getContent(), 'aria-label="Ongoing activity: Three Day Event"'));
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
            ->get(route('activities.calendar', ['month' => '2026-10', 'organization' => $otherUser->id]))
            ->assertOk()
            ->assertSee('My Activity')
            ->assertDontSee('Other Organization Activity')
            ->assertDontSee('Other Planned Activity');
    }

    public function test_admin_calendar_filters_by_organization_college_status_and_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $alpha = User::factory()->create([
            'org_name' => 'Alpha Organization',
            'college' => 'CICS',
            'terms_accepted_at' => now(),
        ]);
        $beta = User::factory()->create([
            'org_name' => 'Beta Organization',
            'college' => 'CET',
            'terms_accepted_at' => now(),
        ]);
        $venue = Venue::create(['name' => 'Filter Hall']);
        $betaVenue = Venue::create(['name' => 'Beta Filter Hall']);
        $this->createActivity($alpha, $venue, 'Alpha Ongoing', '2026-10-10', 'approved');
        $this->createActivity($alpha, $venue, 'Alpha Completed', '2026-10-20', 'closed');
        $this->createActivity($beta, $betaVenue, 'Beta Ongoing', '2026-10-12', 'approved');
        $this->createActivity($alpha, $venue, 'Alpha November', '2026-11-10', 'approved');

        $this->actingAs($admin)
            ->get(route('activities.calendar', ['month' => '2026-10', 'organization' => $alpha->id]))
            ->assertOk()
            ->assertSee('Alpha Ongoing')
            ->assertSee('Alpha Completed')
            ->assertDontSee('Beta Ongoing');

        $this->get(route('activities.calendar', ['month' => '2026-10', 'college' => 'CET']))
            ->assertOk()
            ->assertSee('Beta Ongoing')
            ->assertDontSee('Alpha Ongoing');

        $this->get(route('activities.calendar', ['month' => '2026-10', 'status' => 'Completed']))
            ->assertOk()
            ->assertSee('Alpha Completed')
            ->assertDontSee('Alpha Ongoing')
            ->assertDontSee('Beta Ongoing');

        $rangeResponse = $this->get(route('activities.calendar', [
            'month' => '2026-10',
            'date_from' => '2026-10-09',
            'date_to' => '2026-10-11',
        ]));
        $rangeResponse->assertOk()
            ->assertSee('Alpha Ongoing')
            ->assertDontSee('Alpha Completed')
            ->assertDontSee('Beta Ongoing');
        $rangeResponse->assertSee('date_from=2026-10-09', false)
            ->assertSee('date_to=2026-10-11', false)
            ->assertSee('month=2026-09&amp;date_from=2026-10-09&amp;date_to=2026-10-11', false);
    }

    public function test_calendar_flags_overlapping_venue_requests_but_not_disjoint_schedules(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $firstOrganization = User::factory()->create(['org_name' => 'First Organization', 'terms_accepted_at' => now()]);
        $secondOrganization = User::factory()->create(['org_name' => 'Second Organization', 'terms_accepted_at' => now()]);
        $venue = Venue::create(['name' => 'Conflict Hall']);

        $gpoa = Gpoa::create([
            'user_id' => $firstOrganization->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
        ]);
        $plannedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Overlap Alpha',
            'date' => '2026-10-11',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'venue' => $venue->name,
            'category' => 'Other',
        ]);
        $plannedRequest = $this->createActivity($firstOrganization, $venue, 'Overlap Alpha', '2026-10-12', 'approved', null, '09:00', '10:00');
        $plannedRequest->update(['gpoa_id' => $gpoa->id, 'gpoa_activity_id' => $plannedActivity->id, 'communication_letter' => 'letters/overlap.pdf']);
        $plannedActivity->update(['activity_request_id' => $plannedRequest->id]);
        $this->createActivity($secondOrganization, $venue, 'Overlap Beta', '2026-10-12', 'approved', null, '09:30', '10:30');
        $this->createActivity($firstOrganization, $venue, 'Disjoint Time', '2026-10-12', 'approved', null, '11:00', '12:00');
        $this->createActivity($secondOrganization, $venue, 'Disjoint Date', '2026-10-13', 'approved', null, '09:30', '10:30');

        $response = $this->actingAs($admin)
            ->get(route('activities.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Potential conflict')
            ->assertSee('Overlap Alpha')
            ->assertSee('Overlap Beta')
            ->assertSee('First Organization')
            ->assertSee('Conflict Hall')
            ->assertSee('Ongoing')
            ->assertSee('Planned: Oct 11, 2026')
            ->assertSee('Actual: Oct 12, 2026');

        $this->assertSame(4, substr_count($response->getContent(), 'Potential conflict'));
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
        ?string $endDate = null,
        string $startTime = '09:30',
        ?string $endTime = '10:30'
    ): ActivityRequest {
        return ActivityRequest::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'title' => $title,
            'category' => 'Other',
            'date' => $date,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue' => $venue->name,
            'status' => $status,
        ]);
    }
}