<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\ActivityReport;
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

        $completedRequest = ActivityRequest::create([
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
        ActivityReport::create([
            'activity_request_id' => $completedRequest->id,
            'narrative_content' => ['body' => 'Leadership seminar report.'],
            'narrative_source' => 'generated',
            'submitted_at' => now(),
            'status' => 'pending',
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

    public function test_activity_request_requires_category_and_source_and_prefills_existing_gpoa_values(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Prefilled Activity', '2026-10-10', 'Main Hall');
        $plannedActivity->update(['source_of_funds' => 'Organization Funds']);

        $this->actingAs($user)
            ->get(route('activity-requests.create-from-activity', $plannedActivity))
            ->assertOk()
            ->assertSee('<option value="Symposium" selected>Symposium</option>', false)
            ->assertSee('<option value="Organization Funds" selected>Organization Funds</option>', false);

        $payload = $this->activityRequestPayload($gpoa, $plannedActivity);
        unset($payload['category'], $payload['source_of_funds']);

        $this->from(route('activity-requests.create-from-activity', $plannedActivity))
            ->post(route('activity-requests.store'), $payload)
            ->assertSessionHasErrors(['category', 'source_of_funds']);
    }

    public function test_activity_request_keeps_category_and_source_on_the_request_only(): void
    {
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Request-owned Values', '2026-10-10', 'Main Hall');
        $plannedActivity->update(['source_of_funds' => 'Planned source']);

        $this->actingAs($user)
            ->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
                'category' => 'Outreach',
                'source_of_funds' => 'Request source',
            ]))
            ->assertRedirect();

        $request = ActivityRequest::query()->where('title', 'Request-owned Values')->firstOrFail();
        $this->assertSame('Outreach', $request->category);
        $this->assertSame('Request source', $request->source_of_funds);
        $this->assertSame('Symposium', $plannedActivity->fresh()->category);
        $this->assertSame('Planned source', $plannedActivity->fresh()->source_of_funds);
    }

    public function test_category_limit_is_checked_when_category_is_selected_for_an_uncategorized_gpoa_item(): void
    {
        config(['gpoa_activity_limits.Environmental' => 1]);
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Limit Check Activity', '2026-10-10', 'Main Hall');
        $plannedActivity->update(['category' => null]);
        ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'title' => 'Existing Symposium Request',
            'date' => today()->addDay()->toDateString(),
            'venue' => 'Other Hall',
            'category' => 'Makakalikasan (Clean and Green)',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->from(route('activity-requests.create-from-activity', $plannedActivity))
            ->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity, [
                'category' => 'Makakalikasan (Clean and Green)',
            ]))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseMissing('activity_requests', ['title' => 'Limit Check Activity']);
    }

    public function test_past_activity_without_narrative_report_blocks_new_requests_and_shows_report_link(): void
    {
        $this->travelTo(today()->setDate(2026, 10, 2)->startOfDay());
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('New Planned Event', '2026-10-10', 'Main Hall');
        $outstanding = $this->createExistingRequest($user, $gpoa, 'Past Activity Needs Report', '2026-09-30');

        $this->actingAs($user)
            ->get(route('activity-requests.create'))
            ->assertRedirect(route('activity-monitor.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Past Activity Needs Report'));

        $this->get(route('activity-requests.create-from-activity', $plannedActivity))
            ->assertRedirect(route('activity-monitor.index'));

        $this->get(route('activity-monitor.index'))
            ->assertOk()
            ->assertSee('You have 1 activity report to submit before requesting a new activity')
            ->assertSee(route('activity-reports.create', $outstanding), false)
            ->assertSee('Submit pending report first')
            ->assertDontSee('Request activity');
    }

    public function test_store_cannot_be_used_to_bypass_the_outstanding_report_guard(): void
    {
        $this->travelTo(today()->setDate(2026, 10, 2)->startOfDay());
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('Direct Post Event', '2026-10-10', 'Main Hall');
        $this->createExistingRequest($user, $gpoa, 'Outstanding Before Direct Post', '2026-10-01');

        $this->actingAs($user)
            ->from(route('activity-monitor.index'))
            ->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity))
            ->assertRedirect(route('activity-monitor.index'))
            ->assertSessionHasErrors('activity_request');

        $this->assertDatabaseMissing('activity_requests', ['title' => 'Direct Post Event']);
    }

    public function test_new_requests_are_allowed_after_the_previous_narrative_report_is_submitted(): void
    {
        $this->travelTo(today()->setDate(2026, 10, 2)->startOfDay());
        [$user, $gpoa, $plannedActivity] = $this->makeRequestContext('After Report Event', '2026-10-10', 'Main Hall');
        $previousRequest = $this->createExistingRequest($user, $gpoa, 'Past Activity Reported', '2026-09-30');
        $previousRequest->update(['venue' => 'Previous Hall']);
        ActivityReport::create([
            'activity_request_id' => $previousRequest->id,
            'narrative_content' => ['body' => 'Submitted before the new request.'],
            'narrative_source' => 'generated',
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('activity-requests.create'))
            ->assertOk();
        $this->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity))
            ->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
    }

    public function test_earlier_future_activity_without_a_report_does_not_block_new_requests(): void
    {
        $this->travelTo(today()->setDate(2026, 10, 2)->startOfDay());
        [$user, $gpoa] = $this->makeRequestContext('Future Existing Planned', '2026-10-20', 'Main Hall');
        $this->createExistingRequest($user, $gpoa, 'Future Existing Request', '2026-10-20');

        $this->actingAs($user)
            ->get(route('activity-requests.create'))
            ->assertOk();
    }

    public function test_needs_revision_report_remains_outstanding(): void
    {
        $this->travelTo(today()->setDate(2026, 10, 2)->startOfDay());
        [$user, $gpoa] = $this->makeRequestContext('Needs Revision New Event', '2026-10-10', 'Main Hall');
        $previousRequest = $this->createExistingRequest($user, $gpoa, 'Past Report Needs Revision', '2026-09-30');
        ActivityReport::create([
            'activity_request_id' => $previousRequest->id,
            'narrative_content' => ['body' => 'Needs edits.'],
            'narrative_source' => 'generated',
            'submitted_at' => now(),
            'status' => 'needs_revision',
        ]);

        $this->actingAs($user)
            ->get(route('activity-requests.create'))
            ->assertRedirect(route('activity-monitor.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Past Report Needs Revision'));
    }

    public function test_admin_is_not_blocked_by_organization_outstanding_report_rule(): void
    {
        $this->travelTo(today()->setDate(2026, 10, 2)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin', 'term' => '1st Term', 'school_year' => '2026-2027']);
        $gpoa = Gpoa::create([
            'user_id' => $admin->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $this->createExistingRequest($admin, $gpoa, 'Admin Past Activity', '2026-09-30');
        GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Admin New Activity',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
        ]);

        $this->actingAs($admin)
            ->get(route('activity-requests.create'))
            ->assertOk()
            ->assertDontSee('Submit the pending report');
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
        $this->travelTo('2026-10-01 09:00:00');

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
            ->assertSee(route('activity-requests.create-from-activity', $plannedActivity));

        $this->get(route('activity-requests.create', ['gpoa' => $gpoa->id, 'activity' => $plannedActivity->id]))
            ->assertRedirect(route('activity-requests.create-from-activity', $plannedActivity));

        $form = $this->get(route('activity-requests.create-from-activity', $plannedActivity));
        $form->assertOk()
            ->assertSee('<option value="' . $plannedActivity->id . '" selected>', false)
            ->assertSee("plannedActivitySelect?.addEventListener('change', () =>", false)
            ->assertSee('prefillFromPlannedActivity(false)', false)
            ->assertSee('All details were filled from your GPOA. Review them before submitting.')
            ->assertSee('Reset to GPOA values')
            ->assertDontSee('!!};', false)
            ->assertSee('<option value="UniFast"', false)
            ->assertSee('<option value="Cash on Hand"', false);

        preg_match('/<script type="application\/json" id="activity-request-config">(.*?)<\/script>/s', $form->getContent(), $configMatch);
        $config = json_decode($configMatch[1], true, 512, JSON_THROW_ON_ERROR);
        $plannedActivityData = $config['plannedActivities'][(string) $plannedActivity->id];

        $this->assertSame('Symposium', $plannedActivityData['category']);
        $this->assertSame([4, 13], $plannedActivityData['sdgs']);
        $this->assertSame('2026-10-03', $plannedActivityData['date']);
        $this->assertSame('Celebrate teachers.', $plannedActivityData['objectives']);
        $this->assertSame('Staff feel appreciated.', $plannedActivityData['expected_outcome']);
        $this->assertSame('Recognition program.', $plannedActivityData['plan_key_strategy']);
        $this->assertSame('2026-10-04', $plannedActivityData['end_date']);
        $this->assertSame('09:30', $plannedActivityData['start_time']);
        $this->assertSame('15:45', $plannedActivityData['end_time']);
        $this->assertSame('Main Hall', $plannedActivityData['venue']);
        $this->assertSame('Teachers', $plannedActivityData['target_participants']);
        $this->assertSame('Student Council', $plannedActivityData['person_in_charge']);
        $this->assertSame('Sound system', $plannedActivityData['facilities_materials']);
        $this->assertSame('2500.00', $plannedActivityData['estimated_budget']);
        $this->assertSame('Organization Funds', $plannedActivityData['source_of_funds']);

        $response = $this->post(route('activity-requests.store'), $this->activityRequestPayload($gpoa, $plannedActivity));

        $response->assertRedirect(route('activity-requests.show', ActivityRequest::latest('id')->firstOrFail()));
        $this->assertDatabaseHas('activity_requests', [
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $plannedActivity->id,
            'title' => "Teachers' Day",
        ]);
    }

    public function test_activity_prefill_uses_its_own_gpoa_even_outside_the_users_current_term(): void
    {
        $this->travelTo('2026-10-01 09:00:00');

        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '2nd Term',
            'school_year' => '2027-2028',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Cross Term Planned Activity',
            'category' => 'Symposium',
            'sdgs' => [4, 8],
            'date' => today()->addMonths(3)->startOfMonth()->toDateString(),
            'date_is_month_only' => true,
            'end_date' => today()->addMonths(3)->startOfMonth()->addDay()->toDateString(),
            'start_time' => '09:30:00',
            'end_time' => '15:45:00',
            'venue' => 'Main Hall',
            'objectives' => 'Cross-term objectives',
            'expected_outcome' => 'Cross-term outcome',
            'plan_key_strategy' => 'Cross-term strategy',
            'target_participants' => 'Students',
            'person_in_charge' => 'Council',
            'facilities_materials' => 'Projector',
            'estimated_budget' => 2400,
            'source_of_funds' => 'Organization Funds',
        ]);

        $this->actingAs($user)
            ->get(route('activity-requests.create-from-activity', $activity))
            ->assertOk()
            ->assertSee('Cross Term Planned Activity')
            ->assertSee('value="Cross Term Planned Activity"', false)
            ->assertSee('name="gpoa_id" value="' . $gpoa->id . '"', false)
            ->assertSee('name="gpoa_activity_id" value="' . $activity->id . '"', false)
            ->assertSee('name="date"', false)
            ->assertSee('id="date" type="date" name="date" value=""', false)
            ->assertSee('value="09:30"', false)
            ->assertSee('Cross-term objectives')
            ->assertSee('Missing from your GPOA: Date. Complete the highlighted fields before submitting.');

        $this->withSession(['_old_input' => [
            'title' => 'Manually edited title',
            'category' => 'Convocation',
            'date' => today()->addMonths(4)->toDateString(),
        ]])->get(route('activity-requests.create-from-activity', $activity))
            ->assertOk()
            ->assertSee('value="Manually edited title"', false)
            ->assertSee('<option value="Convocation" selected>', false)
            ->assertSee('value="' . today()->addMonths(4)->toDateString() . '"', false);
    }

    public function test_activity_prefill_is_owner_scoped_and_handles_archived_or_requested_activities(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Protected Planned Activity',
            'date' => today()->addDay()->toDateString(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('activity-requests.create-from-activity', $activity))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('activity-requests.create-from-activity', $activity))
            ->assertOk();

        $activity->update(['archived_at' => now()]);
        $this->get(route('activity-requests.create-from-activity', $activity))
            ->assertRedirect(route('activity-monitor.index'))
            ->assertSessionHas('error');
        $activity->update(['archived_at' => null]);

        $activityRequest = ActivityRequest::create([
            'user_id' => $owner->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $activity->update(['activity_request_id' => $activityRequest->id]);

        $this->get(route('activity-requests.create-from-activity', $activity))
            ->assertRedirect(route('activity-requests.show', $activityRequest))
            ->assertSessionHas('info');
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
            'source_of_funds' => null,
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

    private function createExistingRequest(User $user, Gpoa $gpoa, string $title, string $date): ActivityRequest
    {
        return ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'title' => $title,
            'date' => $date,
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
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
