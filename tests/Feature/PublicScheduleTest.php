<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_only_upcoming_approved_safe_activity_fields(): void
    {
        $owner = User::factory()->create(['org_name' => 'Private Organization Name']);

        ActivityRequest::create([
            'user_id' => $owner->id,
            'title' => 'Public Campus Lecture',
            'category' => 'Academic',
            'date' => today()->addDays(5)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'venue' => 'Main Hall',
            'description' => 'Internal description must not be exposed.',
            'objectives' => 'Internal objectives must not be exposed.',
            'expected_outcome' => 'Internal outcome must not be exposed.',
            'plan_key_strategy' => 'Internal strategy must not be exposed.',
            'target_participants' => 'Private target group',
            'person_in_charge' => 'Private Contact Name',
            'facilities_materials' => 'Internal materials must not be exposed.',
            'estimated_budget' => 98765.43,
            'source_of_funds' => 'Private Fund Source',
            'remarks' => 'Internal remark secret.',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);

        $this->createRequest($owner, 'Pending Event', today()->addDays(3)->toDateString(), ActivityRequest::STATUS_PENDING);
        $this->createRequest($owner, 'Rejected Event', today()->addDays(4)->toDateString(), ActivityRequest::STATUS_REJECTED);
        $this->createRequest($owner, 'Past Event', today()->subDays(2)->toDateString(), ActivityRequest::STATUS_APPROVED);

        $response = $this->get(route('public.schedule'));

        $response->assertOk()
            ->assertSee('Public Campus Lecture')
            ->assertSee('Academic')
            ->assertSee('Main Hall')
            ->assertSee('09:00')
            ->assertDontSee('Pending Event')
            ->assertDontSee('Rejected Event')
            ->assertDontSee('Past Event')
            ->assertDontSee('Private Organization Name')
            ->assertDontSee('Private Contact Name')
            ->assertDontSee('98765.43')
            ->assertDontSee('Internal remark secret.')
            ->assertDontSee('Internal description must not be exposed.');

        $response->assertSessionHasNoErrors();
    }

    public function test_public_schedule_is_separate_from_the_existing_public_activities_route(): void
    {
        $this->get(route('public.schedule'))
            ->assertOk()
            ->assertSee('Upcoming Schedule');

        $this->get(route('public.activities'))
            ->assertOk()
            ->assertSee('Public Activities');
    }

    private function createRequest(User $owner, string $title, string $date, string $status): ActivityRequest
    {
        return ActivityRequest::create([
            'user_id' => $owner->id,
            'title' => $title,
            'category' => 'Other',
            'date' => $date,
            'venue' => 'Main Hall',
            'status' => $status,
        ]);
    }
}
