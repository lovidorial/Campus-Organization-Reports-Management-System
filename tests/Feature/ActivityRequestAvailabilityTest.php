<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
