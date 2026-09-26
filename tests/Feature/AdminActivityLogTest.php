<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_activity_logs_by_actor_action_subject_and_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $actor = User::factory()->create(['name' => 'Morgan Secretary', 'email' => 'morgan@example.test']);

        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'GPOA approved',
            'event' => 'approved',
            'subject_type' => 'App\\Models\\Gpoa',
            'subject_id' => 41,
            'causer_type' => User::class,
            'causer_id' => $actor->id,
            'created_at' => '2026-09-20 12:00:00',
            'updated_at' => '2026-09-20 12:00:00',
        ]);
        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'Profile updated',
            'event' => 'updated',
            'subject_type' => 'App\\Models\\User',
            'subject_id' => $actor->id,
            'causer_type' => User::class,
            'causer_id' => $actor->id,
            'created_at' => '2026-09-10 12:00:00',
            'updated_at' => '2026-09-10 12:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.index', [
            'actor' => 'Morgan',
            'action' => 'approved',
            'subject' => '41',
            'date_from' => '2026-09-19',
            'date_to' => '2026-09-21',
        ]));

        $response->assertOk();
        $response->assertSee('Morgan Secretary');
        $response->assertSee('GPOA approved');
        $response->assertSee('Gpoa #41');
        $response->assertDontSee('Profile updated');
    }
}