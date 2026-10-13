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
        /** @var User $admin */
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
        $this->assertSame(2, substr_count($response->getContent(), '<tr>'));
    }

    public function test_activity_type_filter_exact_matches_event_or_description(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([
            ['description' => 'Backup restored', 'event' => 'backup_restored'],
            ['description' => 'backup_restored', 'event' => null],
            ['description' => 'backup_restored by operator', 'event' => null],
            ['description' => 'Other action', 'event' => 'backup_restored_archive'],
        ] as $entry) {
            ActivityLog::create([
                'log_name' => 'default',
                'description' => $entry['description'],
                'event' => $entry['event'],
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.index', [
            'action' => 'backup_restored',
        ]));

        $response->assertOk();
        $response->assertSee('Backup Restored');
        $this->assertSame(3, substr_count($response->getContent(), '<tr>'));
    }

    public function test_unmapped_activity_code_appears_with_generated_label(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'A custom audit event',
            'event' => 'custom.audit_reviewed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.index'));

        $response->assertOk();
        $response->assertSee('<option value="custom.audit_reviewed"', false);
        $response->assertSee('Custom Audit Reviewed');
    }

    public function test_backup_activity_type_is_listed_once_without_free_text_descriptions(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([
            'Created local backup backup_2026-10-08_134847.zip',
            'Created local backup backup_2026-10-10_180632.zip',
        ] as $description) {
            ActivityLog::create([
                'log_name' => 'backups',
                'description' => $description,
                'event' => 'backup_created',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.index'));
        $response->assertOk();

        preg_match('/<select id="action".*?<\/select>/s', $response->getContent(), $matches);
        $dropdown = $matches[0] ?? '';

        $this->assertSame(1, substr_count($dropdown, '<option value="backup_created"'));
        $this->assertStringContainsString('Backup Created', $dropdown);
        $this->assertStringNotContainsString('Created local backup', $dropdown);
        $this->assertStringNotContainsString('backup_2026-10-08_134847.zip', $dropdown);
        $this->assertStringNotContainsString('backup_2026-10-10_180632.zip', $dropdown);
    }

    public function test_clear_filters_resets_query_and_pagination_to_first_page(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $actor = User::factory()->create(['name' => 'Filter Reset User']);

        foreach (range(1, 26) as $number) {
            ActivityLog::create([
                'log_name' => 'default',
                'description' => "clear test {$number}",
                'causer_type' => User::class,
                'causer_id' => $actor->id,
                'created_at' => now()->subMinutes($number),
                'updated_at' => now()->subMinutes($number),
            ]);
        }

        $filteredResponse = $this->actingAs($admin)->get(route('admin.activity-logs.index', [
            'actor' => 'Filter Reset User',
            'page' => 2,
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('href="' . route('admin.activity-logs.index') . '"', false);
        $this->assertSame(2, substr_count($filteredResponse->getContent(), '<tr>'));

        $clearedResponse = $this->get(route('admin.activity-logs.index'));

        $clearedResponse->assertOk();
        $clearedResponse->assertSee('name="actor" value=""', false);
        $this->assertSame(26, substr_count($clearedResponse->getContent(), '<tr>'));
    }
}