<?php

namespace Tests\Feature;

use App\Models\BackupArchive;
use App\Models\BackupSetting;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $this->actingAs($admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Last successful backup')
            ->assertSee('Last Downloaded')
            ->assertSee('Never downloaded')
            ->assertSee('php artisan schedule:run');
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

        $this->actingAs($admin)->post(route('admin.backups.store'))->assertRedirect(route('admin.backups.index'));

        $log = ActivityLog::query()->where('event', 'backup_created')->firstOrFail();
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame('backup_manual.zip', $log->properties['filename']);
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
        $service->shouldReceive('restoreBackup')->once()->with(\Mockery::on(
            fn (string $argument): bool => basename(str_replace('\\', '/', $argument)) === $filename
        ))->andReturn(true);
        $service->shouldReceive('listBackupFiles')->once()->andReturn([
            ['name' => 'pre_restore_safety.zip', 'path' => 'unused', 'size' => 64, 'created_at' => time()],
        ]);
        $this->app->instance(BackupService::class, $service);

        try {
            $this->actingAs($admin)->post(route('admin.backups.restore'), [
                'backup_filename' => $filename,
                'confirmation' => 'RESTORE',
            ])->assertRedirect()->assertSessionHas('success');

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
}
