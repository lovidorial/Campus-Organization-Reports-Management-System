<?php

namespace Tests\Feature;

use App\Models\Gpoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GpoaPlannedActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_planned_activities_from_confirmed_rows(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'org_name' => 'Sample Org',
        ]);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->create('gpoa.pdf', 1024, 'application/pdf'),
            'approved_confirmation' => '1',
            'verify' => '1',
            'planned_activities' => [
                [
                    'title' => 'Leadership Seminar',
                    'time_frame' => 'exact_date',
                    'date' => '2026-09-15',
                    'end_date' => '2026-09-16',
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'venue' => 'Main Hall',
                    'category' => 'Symposium',
                    'sdgs' => [4, 8],
                    'objectives' => 'Build leadership skills.',
                    'expected_outcome' => 'Improved confidence.',
                    'target_participants' => 'Student leaders',
                    'person_in_charge' => 'Org Officer',
                    'facilities_materials' => 'Projector',
                    'estimated_budget' => '5000',
                    'source_of_funds' => 'Student Trust Fund',
                    'plan_key_strategy' => 'Workshop and Q&A',
                    'preceding_activity' => null,
                ],
                [
                    'title' => 'Community Outreach',
                    'time_frame' => 'exact_date',
                    'date' => '2026-09-20',
                    'venue' => 'Barangay Hall',
                    'category' => 'Outreach',
                    'sdgs' => [1],
                    'objectives' => 'Support community programs.',
                    'expected_outcome' => 'Useful services delivered.',
                    'target_participants' => 'Barangay residents',
                    'person_in_charge' => 'Volunteer Team',
                    'facilities_materials' => 'Supplies',
                    'estimated_budget' => '2500',
                    'source_of_funds' => 'University Subsidy',
                    'plan_key_strategy' => 'Door-to-door campaign',
                    'preceding_activity' => 'Leadership Seminar',
                ],
            ],
        ]);

        $response->assertRedirect(route('dashboard'));

        $gpoa = Gpoa::first();

        $this->assertNotNull($gpoa);
        $this->assertEquals(2, $gpoa->activities()->count());
        $this->assertDatabaseHas('gpoa_activities', [
            'gpoa_id' => $gpoa->id,
            'title' => 'Leadership Seminar',
            'venue' => 'Main Hall',
            'time_frame' => 'exact_date',
        ]);

        $activity = $gpoa->activities()->where('title', 'Leadership Seminar')->first();
        $this->assertNotNull($activity);
        $this->assertNull($activity->end_date);
        $this->assertSame('09:00', $activity->start_time);
        $this->assertSame('11:00', $activity->end_time);
    }

    public function test_store_accepts_missing_optional_category_and_source_of_funds(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);

        $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->create('gpoa.pdf', 1024, 'application/pdf'),
            'approved_confirmation' => '1',
            'verify' => '1',
            'planned_activities' => [$this->completeActivityPayload()],
        ])->assertRedirect(route('dashboard'));

        $activity = Gpoa::query()->firstOrFail()->activities()->firstOrFail();
        $this->assertNull($activity->category);
        $this->assertNull($activity->source_of_funds);
    }

    public function test_store_still_rejects_missing_person_in_charge(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);
        $activity = $this->completeActivityPayload();
        unset($activity['person_in_charge']);

        $this->actingAs($user)->from(route('gpoa.create'))->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->create('gpoa.pdf', 1024, 'application/pdf'),
            'approved_confirmation' => '1',
            'verify' => '1',
            'planned_activities' => [$activity],
        ])->assertSessionHasErrors('planned_activities.0.person_in_charge');

        $this->assertDatabaseCount('gpoas', 0);
    }

    public function test_store_rejects_a_35th_planned_activity_with_existing_message(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'org_name' => 'Sample Org',
        ]);

        $plannedActivities = array_fill(0, 35, [
            'title' => 'Activity',
            'time_frame' => 'exact_date',
            'date' => '2026-09-15',
        ]);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->create('gpoa.pdf', 1024, 'application/pdf'),
            'approved_confirmation' => '1',
            'verify' => '1',
            'planned_activities' => $plannedActivities,
        ]);

        $response->assertSessionHasErrors('planned_activities');
        $this->assertStringContainsString('A GPOA may contain no more than 34 planned activities.', $response->getSession()->get('errors')->get('planned_activities')[0]);
    }

    public function test_store_accepts_a_row_with_only_title_and_time_frame(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->create('gpoa.pdf', 1024, 'application/pdf'),
            'approved_confirmation' => '1',
            'verify' => '1',
            'planned_activities' => [[
                'title' => 'Leadership Seminar',
                'time_frame' => 'exact_date',
                'date' => '2026-09-15',
            ]],
        ]);

        $response->assertRedirect(route('dashboard'));

        $activity = Gpoa::first()->activities()->first();

        $this->assertNotNull($activity);
        $this->assertSame('Leadership Seminar', $activity->title);
        $this->assertSame('2026-09-15', $activity->date->toDateString());
        $this->assertSame('exact_date', $activity->time_frame);
        $this->assertNull($activity->venue);
        $this->assertNull($activity->end_date);
    }

    public function test_store_saves_month_only_and_date_range_rows(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->create('gpoa.pdf', 1024, 'application/pdf'),
            'approved_confirmation' => '1',
            'verify' => '1',
            'planned_activities' => [
                [
                    'title' => 'Month Only Activity',
                    'time_frame' => 'month_only',
                    'date' => '2024-11',
                ],
                [
                    'title' => 'Range Activity',
                    'time_frame' => 'date_range',
                    'date' => '2026-10-21',
                    'end_date' => '2026-10-22',
                ],
            ],
        ]);

        $monthOnlyActivity = Gpoa::first()->activities()->where('title', 'Month Only Activity')->first();
        $rangeActivity = Gpoa::first()->activities()->where('title', 'Range Activity')->first();

        $this->assertNotNull($monthOnlyActivity);
        $this->assertSame('2024-11-01', $monthOnlyActivity->date->toDateString());
        $this->assertTrue((bool) $monthOnlyActivity->date_is_month_only);

        $this->assertNotNull($rangeActivity);
        $this->assertSame('2026-10-21', $rangeActivity->date->toDateString());
        $this->assertSame('2026-10-22', $rangeActivity->end_date->toDateString());
        $this->assertFalse((bool) $rangeActivity->date_is_month_only);
    }

    public function test_store_rejects_a_date_range_ending_before_its_start(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->create('gpoa.pdf', 1024, 'application/pdf'),
            'approved_confirmation' => '1',
            'verify' => '1',
            'planned_activities' => [[
                'title' => 'Range Activity',
                'time_frame' => 'date_range',
                'date' => '2026-10-22',
                'end_date' => '2026-10-21',
            ]],
        ]);

        $response->assertSessionHasErrors([
            'planned_activities.0.end_date' => 'Activity 1 end date cannot be before the start date.',
        ]);
        $this->assertDatabaseCount('gpoas', 0);
    }

    private function completeActivityPayload(): array
    {
        return [
            'title' => 'Leadership Seminar',
            'time_frame' => 'exact_date',
            'date' => '2026-10-15',
            'venue' => 'Main Hall',
            'sdgs' => [4],
            'objectives' => 'Build leadership skills.',
            'expected_outcome' => 'Improved confidence.',
            'plan_key_strategy' => 'Workshop and discussion.',
            'target_participants' => 'Student leaders',
            'person_in_charge' => 'Organization officers',
            'facilities_materials' => 'Projector',
            'estimated_budget' => '5000',
        ];
    }
}
