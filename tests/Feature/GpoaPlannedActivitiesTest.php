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
            'verify' => '1',
            'planned_activities' => [
                [
                    'title' => 'Leadership Seminar',
                    'date' => '2026-09-15',
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
        ]);
    }
}
