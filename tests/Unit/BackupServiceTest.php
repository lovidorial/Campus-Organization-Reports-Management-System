<?php

namespace Tests\Unit;

use App\Services\BackupService;

class BackupServiceTest extends \Tests\TestCase
{
    public function test_create_backup_handles_missing_public_storage_directory(): void
    {
        $publicDir = storage_path('app/public');
        $backupDir = storage_path('app/backups');

        if (is_dir($publicDir)) {
            $this->deleteDirectoryRecursively($publicDir);
        }

        $service = new class extends BackupService {
            protected function dumpDatabase(string $destination): void
            {
                file_put_contents($destination, "-- backup test\n");
            }
        };

        $result = $service->createBackup();

        $this->assertFileExists($result['path']);
        $this->assertFileExists($backupDir . DIRECTORY_SEPARATOR . $result['filename']);
        $this->assertGreaterThan(0, $result['size']);

        if (is_dir($publicDir)) {
            $this->deleteDirectoryRecursively($publicDir);
        }

        if (! is_dir($publicDir)) {
            mkdir($publicDir, 0777, true);
        }
    }

    protected function deleteDirectoryRecursively(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursively($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
