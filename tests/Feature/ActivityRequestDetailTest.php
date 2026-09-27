<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
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
            'is_urgent' => true,
            'urgent_reason' => 'Required by a campus directive.',
            'communication_letter' => 'uploads/comm/letter.pdf',
            'reservation_slip' => 'uploads/reservations/slip.pdf',
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
            ->assertSee('45')
            ->assertSee('Jane Week')
            ->assertSee('Projector and sound system')
            ->assertSee('1,250.50')
            ->assertSee('Student funds')
            ->assertSee('Leadership Orientation')
            ->assertSee('Institutional')
            ->assertSee('4, 16')
            ->assertSee('Bring printed materials.')
            ->assertSee('Prior rejection notes.')
            ->assertSee('Required by a campus directive.')
            ->assertSee('Opening Remarks')
            ->assertSee('letter.pdf')
            ->assertSee('slip.pdf');

        $this->actingAs($owner)
            ->get(route('activity-requests.index'))
            ->assertOk()
            ->assertSee('Reserved');

        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $this->actingAs($admin)->get(route('activity-requests.show', $request))->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.activities'))
            ->assertOk()
            ->assertSee('Reserved');
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
}
