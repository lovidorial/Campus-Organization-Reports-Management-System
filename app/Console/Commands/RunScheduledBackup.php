<?php

namespace App\Console\Commands;

use App\Models\BackupSetting;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RunScheduledBackup extends Command
{
    protected $signature = 'backup:run {--source=scheduled}';

    protected $description = 'Run scheduled backups when enabled';

    public function handle(BackupService $backupService): int
    {
        if (! Schema::hasTable('backup_settings')) {
            $this->warn('Backup settings table not found. Run the backup migration first.');

            return self::SUCCESS;
        }

        $settings = BackupSetting::query()->first();

        if (! $settings || ! $backupService->isBackupDue($settings)) {
            return self::SUCCESS;
        }

        $lock = Cache::lock('backup:execution', 86400);
        if (! $lock->get()) {
            return self::SUCCESS;
        }

        try {
            $settings->refresh();
            if (! $backupService->isBackupDue($settings)) {
                return self::SUCCESS;
            }

            $created = $backupService->createBackup();
            $settings->update([
                'last_run_at' => now(),
            ]);
            $backupService->pruneOldBackups();
            activity('backups')
                ->event('backup_created')
                ->withProperties([
                    'filename' => $created['filename'],
                    'size' => $created['size'],
                    'source' => (string) $this->option('source'),
                ])
                ->log('Scheduled local backup created: '.$created['filename']);
        } catch (\Throwable $exception) {
            Log::error('Scheduled backup failed.', ['exception' => $exception]);
            if (Schema::hasColumn('backup_settings', 'last_error')) {
                $settings->forceFill(['last_error' => $exception->getMessage()])->save();
            }
            $this->error('Scheduled backup failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }

        $this->info('Backup created: '.$created['filename']);

        return self::SUCCESS;
    }
}
