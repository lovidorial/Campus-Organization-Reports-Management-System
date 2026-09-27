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
        $requestsResponse->assertOk();
        $requestsResponse->assertSee('Leadership Seminar');

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

    public function test_activity_requests_inside_seven_days_require_urgent_flag(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Soon Event',
            today()->addDays(6)->toDateString(),
            'Main Hall'
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity));

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('activity_requests', 0);
    }

    public function test_activity_request_exactly_seven_days_ahead_is_allowed(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Seven Day Event',
            today()->addDays(7)->toDateString(),
            'Main Hall'
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity));

        $response->assertRedirect(route('activity-requests.index'));
        $this->assertDatabaseHas('activity_requests', ['title' => 'Seven Day Event', 'is_urgent' => false]);
    }

    public function test_urgent_activity_requires_a_reason(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Urgent Event',
            today()->addDays(2)->toDateString(),
            'Main Hall'
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'is_urgent' => '1',
        ]));

        $response->assertSessionHasErrors('urgent_reason');
        $this->assertDatabaseCount('activity_requests', 0);
    }

    public function test_urgent_activity_with_reason_can_be_submitted_and_uses_venue_id(): void
    {
        Storage::fake('public');
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext(
            'Urgent Event',
            today()->addDays(2)->toDateString(),
            ' Main Hall '
        );

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'is_urgent' => '1',
            'urgent_reason' => 'Required for a time-sensitive campus response.',
        ]));

        $response->assertRedirect(route('activity-requests.index'));
        $request = ActivityRequest::firstOrFail();
        $this->assertTrue($request->is_urgent);
        $this->assertSame('Required for a time-sensitive campus response.', $request->urgent_reason);
        $this->assertNotNull($request->venue_id);
        $this->assertSame('Main Hall', Venue::findOrFail($request->venue_id)->name);

        $this->actingAs($user)
            ->get(route('activity-requests.index'))
            ->assertOk()
            ->assertSee('Urgent');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.activities'))
            ->assertOk()
            ->assertSee('Urgent');
    }

    public function test_admin_cannot_approve_a_request_with_an_overlapping_venue_booking(): void
    {
        $this->createApprovedActivity('Existing Event', '2026-10-10', 'Main Hall', '08:00', '14:30');
        $requester = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $requester->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $pendingRequest = ActivityRequest::create([
            'user_id' => $requester->id,
            'gpoa_id' => $gpoa->id,
            'title' => 'Conflicting Event',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'category' => 'Symposium',
            'sdgs' => [4],
            'objectives' => 'Coordinate a campus event.',
            'expected_outcome' => 'Successful event.',
            'plan_key_strategy' => 'Coordinate resources.',
            'target_participants' => 'Students',
            'person_in_charge' => 'Organization officers',
            'facilities_materials' => 'Main Hall',
            'estimated_budget' => 1000,
            'source_of_funds' => 'Organization Funds',
            'communication_letter' => 'uploads/comm/pending.pdf',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.approve', $pendingRequest->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('activity_requests', [
            'id' => $pendingRequest->id,
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
    }

    public function test_rejected_activity_request_can_be_resubmitted(): void
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

        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'title' => 'Rejected Event',
            'date' => '2026-09-10',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'sdgs' => [4, 8],
            'objectives' => 'Improve skills.',
            'expected_outcome' => 'Better engagement.',
            'plan_key_strategy' => 'Hold workshops.',
            'target_participants' => 'Students',
            'person_in_charge' => 'Org officer',
            'facilities_materials' => 'Projector',
            'estimated_budget' => 5000.00,
            'source_of_funds' => 'Organization Funds',
            'communication_letter' => 'uploads/comm/sample.pdf',
            'status' => ActivityRequest::STATUS_REJECTED,
            'reject_reason' => 'Missing details',
        ]);

        $response = $this->actingAs($user)->post(route('activity-requests.resubmit', $request));

        $response->assertRedirect(route('activity-requests.index'));
        $this->assertDatabaseHas('activity_requests', [
            'id' => $request->id,
            'status' => ActivityRequest::STATUS_PENDING,
            'reject_reason' => null,
        ]);
    }

    public function test_rejected_activity_request_with_a_venue_conflict_cannot_be_resubmitted(): void
    {
        $this->createApprovedActivity('Existing Event', '2026-10-10', 'Main Hall');
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'title' => 'Rejected Event',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'sdgs' => [4],
            'objectives' => 'Improve skills.',
            'expected_outcome' => 'Better engagement.',
            'plan_key_strategy' => 'Hold workshops.',
            'target_participants' => 'Students',
            'person_in_charge' => 'Org officer',
            'facilities_materials' => 'Projector',
            'estimated_budget' => 5000.00,
            'source_of_funds' => 'Organization Funds',
            'communication_letter' => 'uploads/comm/sample.pdf',
            'status' => ActivityRequest::STATUS_REJECTED,
            'reject_reason' => 'Update the schedule.',
        ]);

        $response = $this->actingAs($user)->post(route('activity-requests.resubmit', $request));

        $response->assertSessionHasErrors('venue');
        $this->assertDatabaseHas('activity_requests', [
            'id' => $request->id,
            'status' => ActivityRequest::STATUS_REJECTED,
        ]);
    }

    public function test_activity_request_rejects_a_title_that_does_not_match_the_planned_activity(): void
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

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('activity_requests', 0);
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

        $response->assertRedirect(route('activity-requests.index'));
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

        $response->assertRedirect(route('activity-requests.index'));
        $activityRequest = ActivityRequest::with('programFlows')->firstOrFail();
        $this->assertSame(['Opening Prayer', 'Opening Remarks'], $activityRequest->programFlows->pluck('flow')->all());
        $this->assertSame([0, 1], $activityRequest->programFlows->pluck('sort_order')->all());

        $this->actingAs($user)
            ->get(route('activity-requests.index'))
            ->assertOk()
            ->assertSee('Opening Prayer')
            ->assertSee('Opening Remarks')
            ->assertSee('Person in Charge');

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

        $response->assertRedirect(route('activity-requests.index'));
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

    public function test_closed_and_rejected_requests_do_not_block_venue_reuse(): void
    {
        $this->createApprovedActivity('Closed Event', '2026-10-10', 'Main Hall');
        ActivityRequest::where('title', 'Closed Event')->update(['status' => ActivityRequest::STATUS_CLOSED]);
        $this->createApprovedActivity('Rejected Event', '2026-10-10', 'Main Hall');
        ActivityRequest::where('title', 'Rejected Event')->update(['status' => ActivityRequest::STATUS_REJECTED]);
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('New Event', '2026-10-10', 'Main Hall');

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]));

        $response->assertRedirect(route('activity-requests.index'));
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
