<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\User;
use App\Models\UserBackup;
use Illuminate\Support\Str;
use ZipArchive;

class UserBackupService
{
    public function generate(User $user): UserBackup
    {
        $gpoas = Gpoa::query()
            ->where('user_id', $user->id)
            ->with('activities')
            ->orderBy('id')
            ->get();

        $requests = ActivityRequest::query()
            ->where('user_id', $user->id)
            ->with([
                'gpoa' => fn ($query) => $query->where('user_id', $user->id),
                'gpoaActivity' => fn ($query) => $query->whereHas('gpoa', fn ($gpoaQuery) => $gpoaQuery->where('user_id', $user->id)),
                'report.photos',
            ])
            ->orderBy('id')
            ->get();

        $directory = storage_path('app/user-backups/' . $user->id);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create the private backup directory.');
        }

        $filename = 'user-' . $user->id . '-' . Str::uuid() . '.zip';
        $archivePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $zip = new ZipArchive();

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create the user backup archive.');
        }

        try {
            $zip->addFromString('data/gpoas.json', $this->encode($gpoas));
            $zip->addFromString('data/activity-requests.json', $this->encode($requests));

            foreach ($gpoas as $gpoa) {
                $this->addDocument($zip, $gpoa->document_path, "documents/gpoas/{$gpoa->id}");
            }

            foreach ($requests as $request) {
                $this->addDocument($zip, $request->communication_letter, "documents/activity-requests/{$request->id}/communication");
                if ($request->report) {
                    $this->addDocument($zip, $request->report->narrative_report, "documents/activity-requests/{$request->id}/narrative");
                    foreach ($request->report->photos as $photo) {
                        $this->addDocument($zip, $photo->path, "documents/activity-requests/{$request->id}/photos/{$photo->id}");
                    }
                }
            }

            if (! $zip->close()) {
                throw new \RuntimeException('Unable to finalize the user backup archive.');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($archivePath);
            throw $exception;
        }

        return UserBackup::create([
            'user_id' => $user->id,
            'filename' => $filename,
        ]);
    }

    public function archivePath(UserBackup $backup): string
    {
        $filename = basename($backup->filename);
        abort_unless($filename === $backup->filename && str_ends_with($filename, '.zip'), 404);

        return storage_path('app/user-backups/' . $backup->user_id . '/' . $filename);
    }

    private function encode($records): string
    {
        return json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function addDocument(ZipArchive $zip, ?string $relativePath, string $archiveDirectory): void
    {
        if (! $relativePath || str_contains($relativePath, "\0")) {
            return;
        }

        $normalizedPath = str_replace('\\', '/', $relativePath);
        if (str_starts_with($normalizedPath, '/') || in_array('..', explode('/', $normalizedPath), true)) {
            return;
        }

        $filePath = null;
        foreach ([storage_path('app/private'), storage_path('app/public')] as $storageRoot) {
            $resolvedRoot = realpath($storageRoot);
            $resolvedFile = realpath($storageRoot . DIRECTORY_SEPARATOR . $normalizedPath);
            if ($resolvedRoot && $resolvedFile && is_file($resolvedFile)
                && str_starts_with($resolvedFile, $resolvedRoot . DIRECTORY_SEPARATOR)) {
                $filePath = $resolvedFile;
                break;
            }
        }

        if (! $filePath) {
            return;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $archiveName = $archiveDirectory . ($extension ? '.' . $extension : '');
        $zip->addFile($filePath, $archiveName);
    }
}