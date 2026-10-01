<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityRequestAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_submitted_activity_does_not_block_new_activity_requests(): void
    {
        $user = User::factory()->create([
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        $completedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Leadership Seminar',
            'date' => '2026-08-15',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'objectives' => 'Build leadership skills.',
            'target_participants' => 'Student leaders',
            'estimated_budget' => 5000.00,
            'source_of_funds' => 'Student Trust Funds',
            'person_in_charge' => 'Student Council',
            'sdgs' => [4, 8],
            'preceding_activity' => null,
        ]);

        ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_activity_id' => $completedActivity->id,
            'title' => 'Leadership Seminar',
            'date' => '2026-08-15',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'description' => 'Leadership development event.',
            'participants_count' => 80,
            'communication_letter' => 'uploads/comm/sample.pdf',
            'status' => ActivityRequest::STATUS_REPORT_SUBMITTED,
        ]);

        GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Community Outreach',
            'date' => '2026-09-05',
            'venue' => 'Barangay Hall',
            'category' => 'Outreach',
            'objectives' => 'Support community programs.',
            'target_participants' => 'Barangay residents',
            'estimated_budget' => 2500.00,
            'source_of_funds' => 'University Subsidy',
            'person_in_charge' => 'Volunteer Team',
            'sdgs' => [1, 11],
            'preceding_activity' => null,
        ]);

        $requestsResponse = $this->actingAs($user)->get(route('activity-requests.index'));
        $requestsResponse->assertRedirect(route('activity-monitor.index'));

        $createResponse = $this->actingAs($user)->get(route('activity-requests.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Community Outreach');
    }

    public function test_activity_request_creation_page_shows_allowed_activities_count(): void
    {
        $user = User::factory()->create([
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('activity-requests.create'));

        $response->assertOk();
        $response->assertSee('0 / 33');
        $response->assertDontSee('Participants Override');
        $response->assertDontSee('Preceding Activity');
    }

    public function test_add_from_activity_monitor_prefills_the_planned_activity_and_keeps_its_link(): void
    {
        $user = User::factory()->create([
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $plannedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => "Teachers' Day",
            'category' => 'Symposium',
            'sdgs' => [4, 13],
            'date' => '2026-10-03',
            'end_date' => '2026-10-04',
            'start_time' => '09:30:00',
            'end_time' => '15:45:00',
            'venue' => 'Main Hall',
            'objectives' => 'Celebrate teachers.',
            'expected_outcome' => 'Staff feel appreciated.',
            'plan_key_strategy' => 'Recognition program.',
            'target_participants' => 'Teachers',
            'person_in_charge' => 'Student Council',
            'facilities_materials' => 'Sound system',
            'estimated_budget' => 2500,
            'source_of_funds' => 'Organization Funds',
        ]);

        $monitor = $this->actingAs($user)->get(route('activity-monitor.index'));
        $monitor->assertOk()
            ->assertSee(route('activity-requests.create', ['gpoa' => $gpoa->id, 'activity' => $plannedActivity->id]));

        $form = $this->get(route('activity-requests.create', ['gpoa' => $gpoa->id, 'activity' => $plannedActivity->id]));
        $form->assertOk()
            ->assertSee('<option value="' . $plannedActivity->id . '" selected>', false)
            ->assertSee('\\u0022category\\u0022:\\u0022Symposium\\u0022', false)
            ->assertSee('\\u0022sdgs\\u0022:[4,13]', false)
            ->assertSee('\\u0022date\\u0022:\\u00222026-10-03\\u0022', false)
            ->assertSee('\\u0022end_date\\u0022:\\u00222026-10-04\\u0022', false)
            ->assertSee('\\u0022start_time\\u0022:\\u002209:30\\u0022', false)
            ->assertSee('\\u0022end_time\\u0022:\\u002215:45\\u0022', false)
            ->assertSee('\\u0022venue\\u0022:\\u0022Main Hall\\u0022', false);

        $response = $this->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
        $this->assertDatabaseHas('activity_requests', [
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $plannedActivity->id,
            'title' => "Teachers' Day",
        ]);
    }

    public function test_activity_request_dated_tomorrow_is_accepted(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Tomorrow Event',
            today()->addDay()->toDateString(),
            'Main Hall'
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
        $this->assertDatabaseHas('activity_requests', ['title' => 'Tomorrow Event']);
    }

    public function test_activity_request_dated_yesterday_is_rejected(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Yesterday Event',
            today()->subDay()->toDateString(),
            'Main Hall'
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity));

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('activity_requests', 0);
    }

    public function test_activity_request_uses_venue_id(): void
    {
        Storage::fake('public');
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Tomorrow Event',
            today()->addDay()->toDateString(),
            ' Main Hall '
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
        $request = ActivityRequest::firstOrFail();
        $this->assertNotNull($request->venue_id);
        $this->assertSame('Main Hall', Venue::findOrFail($request->venue_id)->name);
    }

    public function test_activity_request_allows_a_title_that_differs_from_the_planned_activity(): void
    {
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $plannedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'socialization',
            'date' => '2026-10-10',
            'venue' => 'Main Hall ',
            'category' => 'symposium',
            'sdgs' => [4],
        ]);

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'title' => 'Different title',
        ]));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
        $this->assertDatabaseHas('activity_requests', [
            'title' => 'Different title',
            'gpoa_id' => $gpoa->id,
        ]);
    }

    public function test_activity_request_syncs_detail_fields_to_the_linked_planned_activity(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $plannedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Socialization',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'sdgs' => [4],
            'participants_count' => 120,
            'preceding_activity' => 'Orientation',
        ]);

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'title' => 'Socialization',
            'venue' => ' Main Hall',
            'category' => 'SYMPOSIUM',
            'participants_count' => 200,
            'preceding_activity' => 'Removed form override',
        ]));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
        $this->assertDatabaseHas('gpoa_activities', [
            'id' => $plannedActivity->id,
            'objectives' => 'Build student connections.',
            'expected_outcome' => 'Improved collaboration.',
            'plan_key_strategy' => 'Facilitated group activities.',
            'target_participants' => 'Students',
            'person_in_charge' => 'Organization officers',
            'facilities_materials' => 'Sound system',
            'estimated_budget' => 2500.00,
            'source_of_funds' => 'Organization Funds',
            'preceding_activity' => 'Orientation',
        ]);
        $this->assertDatabaseHas('activity_requests', [
            'title' => 'Socialization',
            'participants_count' => null,
            'preceding_activity' => 'Orientation',
        ]);
    }

    public function test_program_flow_rows_are_optional_persisted_in_order_and_shown_to_user_and_admin(): void
    {
        Storage::fake('public');
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Program Flow Event',
            today()->addDays(10)->toDateString(),
            'Main Hall'
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'program_flows' => [
                ['time' => '8:00 AM', 'flow' => 'Opening Prayer', 'person_in_charge' => 'Alex'],
                ['time' => '8:15 AM', 'flow' => 'Opening Remarks', 'person_in_charge' => 'Jordan'],
            ],
        ]));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
        $activityRequest = ActivityRequest::with('programFlows')->firstOrFail();
        $this->assertSame(['Opening Prayer', 'Opening Remarks'], $activityRequest->programFlows->pluck('flow')->all());
        $this->assertSame([0, 1], $activityRequest->programFlows->pluck('sort_order')->all());

        $this->actingAs($user)
            ->get(route('activity-requests.index'))
            ->assertRedirect(route('activity-monitor.index'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.activities'))
            ->assertOk()
            ->assertSee('Opening Prayer')
            ->assertSee('Opening Remarks');
    }

    public function test_same_venue_non_overlapping_times_do_not_conflict(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Afternoon Event', '2026-10-10', 'Main Hall');
        $this->createApprovedActivity('Morning Event', '2026-10-10', 'Main Hall', '08:00', '10:00');

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload(
            $gpoa,
            $plannedActivity,
            ['start_time' => '14:00', 'end_time' => '16:00']
        ));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
    }

    public function test_same_venue_overlapping_times_conflict(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Overlapping Event', '2026-10-10', 'Main Hall');
        $this->createApprovedActivity('Morning Event', '2026-10-10', 'Main Hall', '08:00', '14:30');

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload(
            $gpoa,
            $plannedActivity,
            ['start_time' => '14:00', 'end_time' => '16:00']
        ));

        $response->assertSessionHasErrors('venue');
        $this->assertStringContainsString('already scheduled', $response->getSession()->get('errors')->get('venue')[0]);
    }

    public function test_all_day_activity_conflicts_with_timed_activity_at_same_venue_and_date(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Timed Event', '2026-10-10', 'Main Hall');
        $this->createApprovedActivity('All Day Event', '2026-10-10', 'Main Hall');

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload(
            $gpoa,
            $plannedActivity,
            ['start_time' => '14:00', 'end_time' => '16:00']
        ));

        $response->assertSessionHasErrors('venue');
    }

    public function test_overlapping_multi_day_ranges_conflict_on_a_shared_date(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Multi-day Event', '2026-10-10', 'Main Hall');
        $this->createApprovedActivity('Single-day Event', '2026-10-12', 'Main Hall', '09:00', '11:00');

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'end_date' => '2026-10-12',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]));

        $response->assertSessionHasErrors('venue');
    }

    public function test_cancelled_and_deleted_requests_do_not_block_venue_reuse(): void
    {
        $this->createApprovedActivity('Cancelled Event', '2026-10-10', 'Main Hall');
        ActivityRequest::where('title', 'Cancelled Event')->update(['status' => 'cancelled']);
        $this->createApprovedActivity('Deleted Event', '2026-10-10', 'Main Hall');
        ActivityRequest::where('title', 'Deleted Event')->update(['status' => 'deleted']);
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('New Event', '2026-10-10', 'Main Hall');

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
    }

    private function makeRequestContext(string $title, string $date, string $venue): array
    {
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $plannedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => $title,
            'date' => $date,
            'venue' => $venue,
            'category' => 'Symposium',
            'sdgs' => [4],
        ]);

        return [$user, $gpoa, $plannedActivity];
    }

    private function createApprovedActivity(string $title, string $date, string $venue, ?string $startTime = null, ?string $endTime = null): void
    {
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'title' => $title,
            'date' => $date,
            'venue' => $venue,
            'category' => 'Symposium',
            'sdgs' => [4],
            'objectives' => 'Existing activity.',
            'expected_outcome' => 'Existing outcome.',
            'plan_key_strategy' => 'Existing strategy.',
            'target_participants' => 'Students',
            'person_in_charge' => 'Organization officers',
            'facilities_materials' => 'Main Hall',
            'estimated_budget' => 1000,
            'source_of_funds' => 'Organization Funds',
            'communication_letter' => 'uploads/comm/existing.pdf',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
    }

    private function activityRequestPayload(Gpoa $gpoa, GpoaActivity $plannedActivity, array $overrides = []): array
    {
        return array_merge([
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $plannedActivity->id,
            'title' => $plannedActivity->title,
            'category' => $plannedActivity->category,
            'sdgs' => [4],
            'objectives' => 'Build student connections.',
            'expected_outcome' => 'Improved collaboration.',
            'plan_key_strategy' => 'Facilitated group activities.',
            'date' => $plannedActivity->date->toDateString(),
            'venue' => $plannedActivity->venue,
            'target_participants' => 'Students',
            'person_in_charge' => 'Organization officers',
            'facilities_materials' => 'Sound system',
            'estimated_budget' => 2500,
            'source_of_funds' => 'Organization Funds',
            'communication_letter' => UploadedFile::fake()->create('communication-letter.pdf', 10, 'application/pdf'),
        ], $overrides);
    }
}
