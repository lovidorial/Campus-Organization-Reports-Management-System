<?php

namespace Tests\Feature;

use App\Http\Middleware\RunDueScheduledTasks;
use App\Models\BackupArchive;
use App\Models\BackupSetting;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Tests\TestCase;
use ZipArchive;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_backup_page_renders_with_archive_download_tracking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        BackupSetting::query()->firstOrFail()->update(['frequency' => 'monthly']);
        Cache::put('scheduler_last_run_at', now()->subMinute());

        $this->actingAs($admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Last successful backup')
            ->assertSee('Last Downloaded')
            ->assertSee('Never downloaded')
            ->assertSee('Automatic backup is on. It runs whenever the system is in use.');
    }

    public function test_backup_page_shows_due_warning_and_next_due_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        BackupSetting::query()->firstOrFail()->update([
            'frequency' => 'monthly',
            'last_successful_at' => now()->subDays(45),
        ]);
        Cache::put('scheduler_last_run_at', now()->subMinute());

        $this->actingAs($admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Your backup is due.')
            ->assertSee('Back up now')
            ->assertSee('Next backup due');
    }

    public function test_backup_page_shows_amber_scheduler_status_for_stale_heartbeat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        BackupSetting::query()->firstOrFail()->update(['frequency' => 'monthly']);
        Cache::put('scheduler_last_run_at', now()->subHours(25));

        $this->actingAs($admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Automatic backup has not run recently. Open the system or ask your IT staff to check.');
    }

    public function test_backup_page_uses_five_minute_status_window_when_web_scheduler_is_disabled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        BackupSetting::query()->firstOrFail()->update(['frequency' => 'monthly']);
        config(['backup.web_scheduler' => false]);
        Cache::put('scheduler_last_run_at', now()->subMinutes(4));

        $this->actingAs($admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Automatic backup is on. It runs whenever the system is in use.');

        Cache::put('scheduler_last_run_at', now()->subMinutes(6));
        $this->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Automatic backup has not run recently. Open the system or ask your IT staff to check.');
    }

    public function test_web_scheduler_dispatches_due_backup_only_once_per_minute(): void
    {
        config(['backup.web_scheduler_in_tests' => true]);
        BackupSetting::query()->firstOrFail()->update([
            'frequency' => 'monthly',
            'last_successful_at' => now()->subDays(31),
        ]);
        Cache::forget('scheduler:web-tick');
        $middleware = $this->fakeWebScheduler(true);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk();
        $this->get(route('admin.backups.index'))->assertOk();

        $this->assertSame(1, $middleware->startAttempts);
        $this->assertNotNull(Cache::get('scheduler_last_run_at'));
    }

    public function test_web_scheduler_skips_manual_frequency_and_disabled_setting(): void
    {
        config(['backup.web_scheduler_in_tests' => true]);
        Cache::forget('scheduler:web-tick');
        $middleware = $this->fakeWebScheduler(true);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk();
        $this->assertSame(0, $middleware->startAttempts);

        BackupSetting::query()->firstOrFail()->update([
            'frequency' => 'monthly',
            'last_successful_at' => now()->subDays(31),
        ]);
        Cache::forget('scheduler:web-tick');
        config(['backup.web_scheduler' => false]);

        $this->get(route('admin.backups.index'))->assertOk();
        $this->assertSame(0, $middleware->startAttempts);
    }

    public function test_web_scheduler_spawn_failure_does_not_break_request(): void
    {
        config(['backup.web_scheduler_in_tests' => true]);
        BackupSetting::query()->firstOrFail()->update([
            'frequency' => 'monthly',
            'last_successful_at' => now()->subDays(31),
        ]);
        Cache::forget('scheduler:web-tick');
        $middleware = $this->fakeWebScheduler(false);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk();

        $this->assertSame(1, $middleware->startAttempts);
        $this->assertSame(1, $middleware->fallbackAttempts);
    }

    public function test_saving_an_overdue_schedule_prompts_for_backup_without_starting_one(): void
    {
        config(['backup.web_scheduler_in_tests' => true]);
        Cache::forget('scheduler:web-tick');
        $middleware = $this->fakeWebScheduler(true);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.backups.schedule'), ['frequency' => 'monthly', 'retention_count' => 5])
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('backup_due_after_schedule', true);

        $this->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Your backup is due. Back up now?');
        $this->assertSame(0, $middleware->startAttempts);
    }

    public function test_manual_backup_is_pruned_and_logged_with_admin_as_actor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = \Mockery::mock(BackupService::class);
        $service->shouldReceive('createBackup')->once()->andReturn([
            'filename' => 'backup_manual.zip',
            'path' => storage_path('app/backups/backup_manual.zip'),
            'size' => 256,
        ]);
        $service->shouldReceive('pruneOldBackups')->once();
        $this->app->instance(BackupService::class, $service);

        $this->actingAs($admin)
            ->post(route('admin.backups.store'))
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('download_filename', 'backup_manual.zip');

        $log = ActivityLog::query()->where('event', 'backup_created')->firstOrFail();
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame('backup_manual.zip', $log->properties['filename']);
    }

    public function test_scheduled_backup_logs_web_trigger_source(): void
    {
        $settings = BackupSetting::query()->firstOrFail();
        $settings->update(['frequency' => 'monthly', 'last_successful_at' => now()->subDays(31)]);
        $service = \Mockery::mock(BackupService::class);
        $service->shouldReceive('isBackupDue')->twice()->andReturn(true);
        $service->shouldReceive('createBackup')->once()->andReturn([
            'filename' => 'backup_web_trigger.zip',
            'path' => storage_path('app/backups/backup_web_trigger.zip'),
            'size' => 128,
        ]);
        $service->shouldReceive('pruneOldBackups')->once();
        $this->app->instance(BackupService::class, $service);

        $this->artisan('backup:run', ['--source' => 'web-trigger'])->assertExitCode(0);

        $log = ActivityLog::query()->where('event', 'backup_created')->firstOrFail();
        $this->assertSame('web-trigger', $log->properties['source']);
    }

    public function test_manual_backup_failure_is_logged_and_visible_on_backup_settings(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Manual backup failed.', \Mockery::type('array'));
        $admin = User::factory()->create(['role' => 'admin']);
        $service = \Mockery::mock(BackupService::class);
        $service->shouldReceive('createBackup')->once()->andThrow(new \RuntimeException('simulated dump failure'));
        $this->app->instance(BackupService::class, $service);

        $this->actingAs($admin)
            ->post(route('admin.backups.store'))
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('error', 'Backup failed: simulated dump failure');

        $this->assertSame('simulated dump failure', BackupSetting::query()->firstOrFail()->last_error);
    }

    public function test_backup_download_records_last_download_and_admin_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $filename = 'backup_audit_download.zip';
        file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, 'archive');

        try {
            $this->actingAs($admin)
                ->get(route('admin.backups.download', ['filename' => $filename]))
                ->assertOk()
                ->assertDownload($filename);

            $this->assertNotNull(BackupArchive::query()->where('filename', $filename)->firstOrFail()->last_downloaded_at);
            $this->assertDatabaseHas('activity_log', [
                'event' => 'backup_downloaded',
                'causer_id' => $admin->id,
                'description' => 'Downloaded backup '.$filename,
            ]);
        } finally {
            @unlink($directory.DIRECTORY_SEPARATOR.$filename);
        }
    }

    public function test_schedule_change_is_logged_with_admin_as_actor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $settings = BackupSetting::query()->firstOrFail();

        $this->actingAs($admin)->put(route('admin.backups.schedule'), [
            'frequency' => 'monthly',
            'retention_count' => 7,
        ])->assertRedirect(route('admin.backups.index'));

        $log = ActivityLog::query()->where('event', 'backup_schedule_changed')->firstOrFail();
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame('monthly', $log->properties['frequency']);
        $this->assertSame(7, $log->properties['retention_count']);
        $this->assertSame($settings->id, BackupSetting::query()->firstOrFail()->id);
    }

    public function test_restore_upload_is_limited_by_configured_size(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        config(['backup.max_restore_upload_mb' => 1 / 1024]);
        $archive = UploadedFile::fake()->create('large-backup.zip', 2, 'application/zip');

        $this->actingAs($admin)->post(route('admin.backups.restore'), [
            'backup_file' => $archive,
            'confirmation' => 'RESTORE',
        ])->assertSessionHasErrors('backup_file');
    }

    public function test_restore_returns_json_validation_errors_for_fetch_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.backups.restore'), [
                'backup_filename' => 'backup.zip',
                'confirmation' => 'wrong',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Please type RESTORE to confirm the restore action.');
    }

    public function test_restore_route_writes_audit_entry_on_success(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $filename = 'backup_audit_restore.zip';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database.sql', 'dump');
        $zip->addEmptyDir('storage/public');
        $zip->close();

        $service = \Mockery::mock(BackupService::class);
        $service->shouldReceive('restoreBackup')->twice()->with(\Mockery::on(
            fn (string $argument): bool => basename(str_replace('\\', '/', $argument)) === $filename
        ))->andReturn(true);
        $service->shouldReceive('listBackupFiles')->twice()->andReturn([
            ['name' => 'pre_restore_safety.zip', 'path' => 'unused', 'size' => 64, 'created_at' => time()],
        ]);
        $this->app->instance(BackupService::class, $service);

        try {
            $this->actingAs($admin)->post(route('admin.backups.restore'), [
                'backup_filename' => $filename,
                'confirmation' => 'RESTORE',
            ])->assertRedirect()->assertSessionHas('success');

            $this->postJson(route('admin.backups.restore'), [
                'backup_filename' => $filename,
                'confirmation' => 'RESTORE',
            ])->assertOk()
                ->assertJsonPath('message', 'Backup restored successfully.')
                ->assertJsonPath('redirect', route('admin.backups.index'))
                ->assertSessionHas('success', 'Backup restored successfully.');

            $log = ActivityLog::query()->where('event', 'backup_restored')->firstOrFail();
            $this->assertSame($admin->id, $log->causer_id);
            $this->assertSame($filename, $log->properties['filename']);
            $this->assertSame('pre_restore_safety.zip', $log->properties['pre_restore_backup']);
        } finally {
            @unlink($path);
        }
    }

    public function test_delete_writes_audit_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $filename = 'backup_audit_delete.zip';
        file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, 'archive');

        $this->actingAs($admin)
            ->delete(route('admin.backups.destroy', ['filename' => $filename]))
            ->assertRedirect(route('admin.backups.index'));

        $this->assertDatabaseHas('activity_log', [
            'event' => 'backup_deleted',
            'causer_id' => $admin->id,
            'description' => 'Deleted backup '.$filename,
        ]);
        $this->assertFileDoesNotExist($directory.DIRECTORY_SEPARATOR.$filename);
    }

    private function fakeWebScheduler(bool $spawnSucceeds): object
    {
        $middleware = new class(app(BackupService::class)) extends RunDueScheduledTasks
        {
            public int $startAttempts = 0;

            public int $fallbackAttempts = 0;

            public bool $spawnSucceeds = false;

            protected function startDetachedProcess(): bool
            {
                $this->startAttempts++;

                return $this->spawnSucceeds;
            }

            protected function runAfterResponse(): void
            {
                $this->fallbackAttempts++;
            }
        };

        $middleware->spawnSucceeds = $spawnSucceeds;
        $this->app->instance(RunDueScheduledTasks::class, $middleware);

        return $middleware;
    }
}
