<?php

namespace App\Http\Controllers;

use App\Models\BackupSetting;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backupService)
    {
    }

    public function index(): View
    {
        $backupSetting = BackupSetting::query()->firstOrCreate(
            [],
            [
                'frequency' => 'manual',
                'retention_count' => 5,
            ]
        );

        $backups = collect($this->backupService->listBackupFiles())
            ->map(function (array $backup): array {
                $filePath = storage_path('app/backups/' . $backup['name']);

                if (file_exists($filePath)) {
                    $backup['created_at'] = Carbon::createFromTimestamp(filemtime($filePath))
                        ->setTimezone(config('app.timezone'))
                        ->format('F j, Y g:i A');
                }

                return $backup;
            });

        return view('admin.backups.index', [
            'backups' => $backups,
            'backupSetting' => $backupSetting,
        ]);
    }

    public function store(): RedirectResponse
    {
        $this->backupService->createBackup();

        return redirect()->route('admin.backups.index')->with('success', 'Backup created successfully.');
    }

    public function download(string $filename)
    {
        $safeFilename = $this->validateBackupFilename($filename);
        $path = storage_path('app/backups/' . $safeFilename);

        abort_unless(file_exists($path), 404, 'Backup file not found.');

        return response()->download($path, $safeFilename);
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_file' => ['nullable', 'file', 'mimetypes:application/zip,application/x-zip-compressed'],
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
            $backupPath = storage_path('app/backups/' . $safeFilename);
            abort_unless(file_exists($backupPath), 404, 'Backup file not found.');
        } else {
            return back()->withErrors(['backup_file' => 'Please choose a backup file or select an existing backup.'])->withInput();
        }

        try {
            $this->backupService->restoreBackup($backupPath);
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
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

        return redirect()->route('admin.backups.index')->with('success', 'Backup schedule updated.');
    }

    public function destroy(string $filename): RedirectResponse
    {
        $safeFilename = $this->validateBackupFilename($filename);
        $path = storage_path('app/backups/' . $safeFilename);

        if (file_exists($path)) {
            unlink($path);
        }

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
}
