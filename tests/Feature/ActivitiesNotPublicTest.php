<?php

namespace Tests\Feature;

use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitiesNotPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_activity_bookmark_redirects_without_exposing_activity_titles(): void
    {
        $user = User::factory()->create(['terms_accepted_at' => now()]);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Semester',
            'school_year' => '2025-2026',
            'college' => 'CICS',
        ]);

        GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Confidential Planned Activity',
            'date' => '2026-10-20',
        ]);

        $this->assertDatabaseHas('gpoa_activities', ['title' => 'Confidential Planned Activity']);

        $this->get('/activities')
            ->assertRedirect('/')
            ->assertDontSee('Confidential Planned Activity');
    }
}