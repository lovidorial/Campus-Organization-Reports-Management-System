<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\ActivityReport;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityRequestDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_admin_can_view_all_request_fields_and_program_flow(): void
    {
        $owner = User::factory()->create(['org_name' => 'CICS-SC', 'terms_accepted_at' => now()]);
        $venue = Venue::create(['name' => 'Main Hall']);
        $request = ActivityRequest::create([
            'user_id' => $owner->id,
            'venue_id' => $venue->id,
            'title' => 'Leadership Symposium',
            'category' => 'Academic',
            'venue' => $venue->name,
            'date' => '2026-10-15',
            'end_date' => '2026-10-16',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'description' => 'A detailed description.',
            'objectives' => 'Build leadership skills.',
            'expected_outcome' => 'Stronger student leaders.',
            'plan_key_strategy' => 'Facilitated workshops.',
            'target_participants' => 'Student leaders',
            'participants_count' => 45,
            'person_in_charge' => 'Jane Week',
            'facilities_materials' => 'Projector and sound system',
            'estimated_budget' => 1250.50,
            'source_of_funds' => 'Student funds',
            'preceding_activity' => 'Leadership Orientation',
            'activity_level' => 'Institutional',
            'sdgs' => [4, 16],
            'remarks' => 'Bring printed materials.',
            'reject_reason' => 'Prior rejection notes.',
            'status' => ActivityRequest::STATUS_PENDING,
            'communication_letter' => 'uploads/comm/letter.pdf',
        ]);
        $request->forceFill(['activity_level' => 'Institutional'])->save();
        $request->programFlows()->create([
            'time' => '8:00 AM',
            'flow' => 'Opening Remarks',
            'person_in_charge' => 'Jane Week',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($owner)->get(route('activity-requests.show', $request));

        $response->assertOk()
            ->assertSee('Leadership Symposium')
            ->assertSee('Academic')
            ->assertSee('Main Hall')
            ->assertSee('Reserved')
            ->assertSee('Oct 15, 2026')
            ->assertSee('Oct 16, 2026')
            ->assertSee('08:00 – 17:00')
            ->assertSee('A detailed description.')
            ->assertSee('Build leadership skills.')
            ->assertSee('Stronger student leaders.')
            ->assertSee('Facilitated workshops.')
            ->assertSee('Student leaders')
            ->assertSee('Jane Week')
            ->assertSee('Projector and sound system')
            ->assertSee('1,250.50')
            ->assertSee('Student funds')
            ->assertSee('Institutional')
            ->assertSee('>4</span>', false)
            ->assertSee('>16</span>', false)
            ->assertSee('Bring printed materials.')
            ->assertSee('Prior rejection notes.')
            ->assertSee('Opening Remarks')
            ->assertSee('letter.pdf')
            ->assertDontSee('Preceding Activity')
            ->assertDontSee('Leadership Orientation')
            ->assertDontSee('Participants Count');

        $this->actingAs($owner)
            ->get(route('activity-requests.index'))
            ->assertRedirect(route('activity-monitor.index'));

        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $this->actingAs($admin)->get(route('activity-requests.show', $request))->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.activities'))
            ->assertOk()
            ->assertSee('No recent submissions match these filters.');
    }

    public function test_users_cannot_view_another_users_request(): void
    {
        $owner = User::factory()->create(['terms_accepted_at' => now()]);
        $otherUser = User::factory()->create(['terms_accepted_at' => now()]);
        $request = $this->createRequest($owner, 'Private Request', today()->addDay()->toDateString(), ActivityRequest::STATUS_PENDING);

        $this->actingAs($otherUser)
            ->get(route('activity-requests.show', $request))
            ->assertForbidden();
    }

    public function test_organization_sees_read_only_reviewer_remark_and_report_approval_date_without_admin_identity(): void
    {
        $owner = User::factory()->create(['org_name' => 'CICS-SC', 'terms_accepted_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Private Reviewer Name']);
        $gpoa = Gpoa::create(['user_id' => $owner->id, 'term' => '1st Term', 'school_year' => '2026-2027', 'college' => 'CICS', 'status' => 'approved']);
        $activity = $this->createPlannedActivity($gpoa, '2026-10-15');
        $request = $this->createPlannedRequest($owner, $gpoa, $activity);
        $reviewedAt = now()->subDay();
        $report = ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/approved.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now()->subDays(2),
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => $reviewedAt,
        ]);
        MonitoringResult::create([
            'activity_request_id' => $request->id,
            'gpoa_activity_id' => $activity->id,
            'admin_id' => $admin->id,
            'compliance_status' => 'partial',
            'compliance_notes' => 'Please include participant feedback next time.',
            'recorded_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('activity-requests.show', $request))
            ->assertOk()
            ->assertSee('Reviewer remarks')
            ->assertSee('Partially aligned')
            ->assertSee('Please include participant feedback next time.')
            ->assertSee('Approved on ' . $reviewedAt->format('M d, Y'))
            ->assertDontSee('Private Reviewer Name');
    }

    public function test_activity_number_uses_date_and_id_order_and_is_null_without_a_date(): void
    {
        $owner = User::factory()->create(['terms_accepted_at' => now()]);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'college' => 'CICS',
        ]);
        $firstActivity = $this->createPlannedActivity($gpoa, '2026-10-15');
        $secondActivity = $this->createPlannedActivity($gpoa, '2026-10-15');
        $secondRequest = $this->createPlannedRequest($owner, $gpoa, $secondActivity);

        $this->actingAs($owner)
            ->get(route('activity-requests.show', $secondRequest))
            ->assertOk()
            ->assertSee('Activity #2');

        $this->assertNotSame($firstActivity->id, $secondActivity->id);
    }

    public function test_venue_status_is_available_reserved_or_scheduled_from_related_requests(): void
    {
        $owner = User::factory()->create();
        $availableVenue = Venue::create(['name' => 'Available Hall']);
        $reservedVenue = Venue::create(['name' => 'Reserved Hall']);
        $scheduledVenue = Venue::create(['name' => 'Scheduled Hall']);
        $inProgressVenue = Venue::create(['name' => 'In Progress Hall']);

        $this->createRequest($owner, 'Future Pending', today()->addDay()->toDateString(), ActivityRequest::STATUS_PENDING, $reservedVenue);
        $this->createRequest($owner, 'Today Approved', today()->toDateString(), ActivityRequest::STATUS_APPROVED, $scheduledVenue);
        $this->createRequest($owner, 'In Progress', today()->subDay()->toDateString(), ActivityRequest::STATUS_IN_PROGRESS, $inProgressVenue);

        $this->assertSame('Available', $availableVenue->availability_status);
        $this->assertSame('Reserved', $reservedVenue->availability_status);
        $this->assertSame('Scheduled', $scheduledVenue->availability_status);
        $this->assertSame('Scheduled', $inProgressVenue->availability_status);
    }

    private function createRequest(
        User $owner,
        string $title,
        string $date,
        string $status,
        ?Venue $venue = null
    ): ActivityRequest {
        $venue ??= Venue::create(['name' => $title . ' Venue']);

        return ActivityRequest::create([
            'user_id' => $owner->id,
            'venue_id' => $venue->id,
            'title' => $title,
            'category' => 'Other',
            'date' => $date,
            'venue' => $venue->name,
            'status' => $status,
        ]);
    }

    private function createPlannedActivity(Gpoa $gpoa, string $date): GpoaActivity
    {
        return GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Planned Activity',
            'date' => $date,
            'venue' => 'Main Hall',
            'category' => 'Other',
        ]);
    }

    private function createPlannedRequest(User $owner, Gpoa $gpoa, GpoaActivity $activity): ActivityRequest
    {
        return ActivityRequest::create([
            'user_id' => $owner->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'category' => 'Other',
            'date' => $activity->date,
            'venue' => $activity->venue,
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
    }
}
