<?php

namespace App\Console\Commands;

use App\Models\BackupSetting;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RunScheduledBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Run scheduled backups when enabled';

    public function handle(BackupService $backupService): int
    {
        if (! Schema::hasTable('backup_settings')) {
            $this->warn('Backup settings table not found. Run the backup migration first.');

            return self::SUCCESS;
        }

        $settings = BackupSetting::query()->first();

        if (! $settings || $settings->frequency === 'manual') {
            return self::SUCCESS;
        }

        $now = now();
        $lastRunAt = $settings->last_run_at;

        $shouldRun = match ($settings->frequency) {
            'monthly' => ! $lastRunAt || $lastRunAt->diffInDays($now) >= 30,
            'per_semester' => ! $lastRunAt || $this->currentSemester($now) !== $this->currentSemester($lastRunAt),
            'per_school_year' => ! $lastRunAt || $this->currentSchoolYear($now) !== $this->currentSchoolYear($lastRunAt),
            default => false,
        };

        if (! $shouldRun) {
            return self::SUCCESS;
        }

        $created = $backupService->createBackup();
        $settings->update([
            'last_run_at' => now(),
        ]);
        $backupService->pruneOldBackups();

        $this->info('Backup created: ' . $created['filename']);

        return self::SUCCESS;
    }

    protected function currentSemester(\DateTimeInterface $date): int
    {
        return $date->format('n') >= 1 && $date->format('n') <= 6 ? 1 : 2;
    }

    protected function currentSchoolYear(\DateTimeInterface $date): int
    {
        $month = (int) $date->format('n');

        return $month >= 8 ? (int) $date->format('Y') : (int) $date->format('Y') - 1;
    }
}
