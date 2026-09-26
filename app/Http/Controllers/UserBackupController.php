<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserBackup;
use App\Services\StorageUsageService;
use App\Services\UserBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserBackupController extends Controller
{
    public function index(Request $request, StorageUsageService $storageUsage): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_if($user->isAdmin(), 403);

        $organization = $user->organization
            ?? Organization::query()->where('name', $user->org_name)->first();
        $storageLimitMb = $organization?->storage_limit_mb ?? 500;
        $storageUsedBytes = $organization
            ? $storageUsage->organizationUsageBytes($organization->id)
            : 0;
        $storageUsedMb = $storageUsedBytes / 1048576;
        $storageUsagePercent = (int) floor(($storageUsedMb / max(1, $storageLimitMb)) * 100);
        $isNearStorageLimit = $storageUsagePercent >= 80;
        $backups = UserBackup::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return view('users.my-backup', compact(
            'backups',
            'storageUsedMb',
            'storageLimitMb',
            'storageUsagePercent',
            'isNearStorageLimit'
        ));
    }

    public function export(Request $request, UserBackupService $backupService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_if($user->isAdmin(), 403);
        $backupService->generate($user);

        return redirect()->route('my-backup.index')
            ->with('success', 'Your data export is ready to download.');
    }

    public function download(string $filename, UserBackupService $backupService)
    {
        $backup = UserBackup::query()->where('filename', basename($filename))->firstOrFail();
        $this->authorize('download', $backup);

        $path = $backupService->archivePath($backup);
        abort_unless(is_file($path), 404, 'Backup archive not found. Generate a new export.');

        return response()->download($path, $backup->filename);
    }
}