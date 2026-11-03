<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoredFileMetadata
{
    public static function fromUpload(UploadedFile $file): array
    {
        return [
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    public static function fromContents(string $contents, string $mimeType): array
    {
        return [
            'size' => strlen($contents),
            'mime_type' => $mimeType,
            'sha256' => hash('sha256', $contents),
        ];
    }

    public static function status(?string $path, ?string $checksum, array $disks = ['private', 'public']): string
    {
        if (! filled($path)) {
            return 'Missing';
        }

        foreach ($disks as $diskName) {
            $disk = Storage::disk($diskName);
            if (! $disk->exists($path)) {
                continue;
            }

            if ($checksum !== null) {
                $actualChecksum = hash_file('sha256', $disk->path($path));
                if (! is_string($actualChecksum) || ! hash_equals($checksum, $actualChecksum)) {
                    return 'File unreadable';
                }
            }

            return 'Uploaded';
        }

        return 'File unreadable';
    }
}
