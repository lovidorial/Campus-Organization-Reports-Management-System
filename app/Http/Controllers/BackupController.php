<?php

namespace App\Http\Controllers;

use App\Models\BackupArchive;
use App\Models\BackupSetting;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backupService) {}

    public function index(): View
    {
        $backupSetting = BackupSetting::query()->firstOrCreate(
            [],
            [
                'frequency' => 'manual',
                'retention_count' => 5,
            ]
        );

        $archiveTrackingAvailable = Schema::hasTable('backup_archives');
        $backups = collect($this->backupService->listBackupFiles())->map(function (array $backup) use ($archiveTrackingAvailable): array {
            $archive = $archiveTrackingAvailable
                ? BackupArchive::query()->firstOrCreate(['filename' => $backup['name']])
                : null;
            $backup['created_timestamp'] = (int) $backup['created_at'];
            $backup['created_at'] = Carbon::createFromTimestamp($backup['created_timestamp'])
                ->setTimezone(config('app.timezone'))
                ->format('F j, Y g:i A');
            $backup['last_downloaded_at'] = $archive?->last_downloaded_at;

            return $backup;
        });

        $lastSuccessfulAt = $backupSetting->last_successful_at;
        $backupWarning = $this->backupIsOverdue($backupSetting->frequency, $lastSuccessfulAt);

        return view('admin.backups.index', [
            'backups' => $backups,
            'backupSetting' => $backupSetting,
            'lastSuccessfulAt' => $lastSuccessfulAt,
            'backupWarning' => $backupWarning,
            'phpUploadLimit' => ini_get('upload_max_filesize'),
            'phpPostLimit' => ini_get('post_max_size'),
            'maxRestoreUploadMb' => (int) config('backup.max_restore_upload_mb', 2048),
            'archiveTrackingAvailable' => $archiveTrackingAvailable,
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $backup = $this->backupService->createBackup();
            $this->backupService->pruneOldBackups();
            $this->recordBackupActivity(request(), 'backup_created', [
                'filename' => $backup['filename'],
                'size' => $backup['size'],
            ], 'Created local backup '.$backup['filename']);
        } catch (\Throwable $exception) {
            Log::error('Manual backup failed.', ['exception' => $exception]);
            $this->rememberBackupError($exception->getMessage());

            return redirect()->route('admin.backups.index')->with('error', 'Backup failed: '.$exception->getMessage());
        }

        return redirect()->route('admin.backups.index')->with('success', 'Backup created successfully.');
    }

    public function download(string $filename)
    {
        $safeFilename = $this->validateBackupFilename($filename);
        $path = storage_path('app/backups/'.$safeFilename);

        abort_unless(file_exists($path), 404, 'Backup file not found.');

        if (Schema::hasTable('backup_archives')) {
            BackupArchive::query()->updateOrCreate(
                ['filename' => $safeFilename],
                ['last_downloaded_at' => now()]
            );
        }
        $this->recordBackupActivity(request(), 'backup_downloaded', ['filename' => $safeFilename], 'Downloaded backup '.$safeFilename);

        return response()->download($path, $safeFilename);
    }

    public function restore(Request $request): RedirectResponse
    {
        if ($uploadError = $this->restoreUploadLimitError($request)) {
            return back()->withErrors(['backup_file' => $uploadError])->withInput();
        }

        $maxUploadKilobytes = max(1, (int) config('backup.max_restore_upload_mb', 2048) * 1024);
        $validated = $request->validate([
            'backup_file' => ['nullable', 'file', 'mimetypes:application/zip,application/x-zip-compressed', 'max:'.$maxUploadKilobytes],
            'backup_filename' => ['nullable', 'string', 'max:255'],
            'confirmation' => ['required', 'string'],
        ]);

        $confirmation = strtoupper(trim((string) ($validated['confirmation'] ?? '')));
        if ($confirmation !== 'RESTORE') {
            return back()->withErrors(['confirmation' => 'Please type RESTORE to confirm the restore action.'])->withInput();
        }

        $backupPath = null;

        if ($request->hasFile('backup_file')) {
            $backupPath = $request->file('backup_file')->getRealPath();
        } elseif (! empty($validated['backup_filename'])) {
            $safeFilename = $this->validateBackupFilename($validated['backup_filename']);
            $backupPath = storage_path('app/backups/'.$safeFilename);
            abort_unless(file_exists($backupPath), 404, 'Backup file not found.');
        } else {
            return back()->withErrors(['backup_file' => 'Please choose a backup file or select an existing backup.'])->withInput();
        }

        try {
            $this->backupService->restoreBackup($backupPath);
            $restoredFilename = $request->hasFile('backup_file')
                ? $request->file('backup_file')->getClientOriginalName()
                : basename($backupPath);
            $preRestore = collect($this->backupService->listBackupFiles())
                ->filter(fn (array $file): bool => str_starts_with($file['name'], 'pre_restore_'))
                ->last();
            $this->recordBackupActivity($request, 'backup_restored', [
                'filename' => $restoredFilename,
                'pre_restore_backup' => $preRestore['name'] ?? null,
            ], 'Restored backup '.$restoredFilename);
        } catch (\Throwable $e) {
            Log::error('Backup restore failed.', ['exception' => $e]);
            $this->rememberBackupError($e->getMessage());

            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }

        return back()->with('success', 'Backup restored successfully.');
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'frequency' => ['required', 'in:manual,monthly,per_semester,per_school_year'],
            'retention_count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $settings = BackupSetting::query()->firstOrCreate(
            [],
            [
                'frequency' => 'manual',
                'retention_count' => 5,
            ]
        );

        $settings->update([
            'frequency' => $validated['frequency'],
            'retention_count' => (int) $validated['retention_count'],
        ]);
        $this->recordBackupActivity($request, 'backup_schedule_changed', [
            'frequency' => $settings->frequency,
            'retention_count' => $settings->retention_count,
        ], 'Changed local backup schedule');

        return redirect()->route('admin.backups.index')->with('success', 'Backup schedule updated.');
    }

    public function destroy(string $filename): RedirectResponse
    {
        $safeFilename = $this->validateBackupFilename($filename);
        $path = storage_path('app/backups/'.$safeFilename);

        if (file_exists($path)) {
            unlink($path);
        }
        if (Schema::hasTable('backup_archives')) {
            BackupArchive::query()->where('filename', $safeFilename)->delete();
        }
        $this->recordBackupActivity(request(), 'backup_deleted', ['filename' => $safeFilename], 'Deleted backup '.$safeFilename);

        return redirect()->route('admin.backups.index')->with('success', 'Backup deleted successfully.');
    }

    protected function validateBackupFilename(string $filename): string
    {
        $safeFilename = basename($filename);

        if ($safeFilename !== $filename || str_contains($filename, '..') || str_contains($filename, '/')) {
            abort(400, 'Invalid backup filename.');
        }

        return $safeFilename;
    }

    protected function formatFileTimestampForDisplay(string $filePath): string
    {
        return Carbon::createFromTimestamp(filemtime($filePath))
            ->setTimezone(config('app.timezone'))
            ->format('F j, Y g:i A');
    }

    private function backupIsOverdue(string $frequency, ?Carbon $lastSuccessfulAt): bool
    {
        if ($frequency === 'manual') {
            return false;
        }
        if (! $lastSuccessfulAt) {
            return true;
        }

        return match ($frequency) {
            'monthly' => $lastSuccessfulAt->lt(now()->subDays(30)),
            'per_semester' => $this->semesterKey($lastSuccessfulAt) !== $this->semesterKey(now()),
            'per_school_year' => $this->schoolYearKey($lastSuccessfulAt) !== $this->schoolYearKey(now()),
            default => false,
        };
    }

    private function semesterKey(Carbon $date): string
    {
        return $date->format('Y').'-'.((int) $date->format('n') <= 6 ? '1' : '2');
    }

    private function schoolYearKey(Carbon $date): int
    {
        return (int) $date->format('Y') - ((int) $date->format('n') < 8 ? 1 : 0);
    }

    private function recordBackupActivity(Request $request, string $event, array $properties, string $description): void
    {
        activity('backups')
            ->causedBy($request->user())
            ->event($event)
            ->withProperties($properties)
            ->log($description);
    }

    private function rememberBackupError(string $message): void
    {
        $settings = BackupSetting::query()->first();
        if ($settings && Schema::hasColumn('backup_settings', 'last_error')) {
            $settings->forceFill(['last_error' => $message])->save();
        }
    }

    private function restoreUploadLimitError(Request $request): ?string
    {
        $postLimit = $this->iniBytes((string) ini_get('post_max_size'));
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        if ($postLimit > 0 && $contentLength > $postLimit) {
            return 'The uploaded request exceeds PHP post_max_size ('.ini_get('post_max_size').'). Increase post_max_size and upload_max_filesize or choose a smaller ZIP.';
        }

        $uploaded = $request->allFiles()['backup_file'] ?? null;
        if ($uploaded instanceof UploadedFile && $uploaded->getError() === UPLOAD_ERR_INI_SIZE) {
            return 'The ZIP exceeds PHP upload_max_filesize ('.ini_get('upload_max_filesize').'). Increase upload_max_filesize and post_max_size or choose a smaller ZIP.';
        }
        if ($uploaded instanceof UploadedFile && $uploaded->getError() === UPLOAD_ERR_FORM_SIZE) {
            return 'The ZIP exceeds the form upload limit. Choose a smaller file.';
        }

        return null;
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) ($number * match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        });
    }
}
