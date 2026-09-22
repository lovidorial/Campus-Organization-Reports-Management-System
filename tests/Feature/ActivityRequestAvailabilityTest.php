<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
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
        ]);

        $response = $this->actingAs($user)->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
            'title' => 'Socialization',
            'venue' => ' Main Hall',
            'category' => 'SYMPOSIUM',
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
            'preceding_activity' => 'Orientation',
            'communication_letter' => UploadedFile::fake()->create('communication-letter.pdf', 10, 'application/pdf'),
        ], $overrides);
    }
}
