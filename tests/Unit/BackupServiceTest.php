<?php

namespace Tests\Unit;

use App\Models\BackupSetting;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryRoots = [];

    public function test_create_backup_handles_missing_public_storage_directory(): void
    {
        $root = $this->tempRoot();
        $service = new TestableBackupService($root);

        $result = $service->createBackup();

        $this->assertFileExists($result['path']);
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$result['filename']);
        $this->assertGreaterThan(0, $result['size']);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($result['path']) === true);
        $this->assertNotFalse($zip->locateName('database.sql'));
        $this->assertNotFalse($zip->locateName('storage/public/'));
        $zip->close();
    }

    public function test_restore_failure_reimports_pre_restore_dump_and_restores_files(): void
    {
        $root = $this->tempRoot();
        $publicPath = $root.DIRECTORY_SEPARATOR.'public';
        mkdir($publicPath, 0777, true);
        file_put_contents($publicPath.DIRECTORY_SEPARATOR.'keep.txt', 'before restore');

        $targetArchive = $root.DIRECTORY_SEPARATOR.'target.zip';
        $this->createArchive($targetArchive, 'target-state', 'replacement.txt', 'target files');
        $service = new TestableBackupService($root);

        try {
            $service->restoreBackup($targetArchive);
            $this->fail('Expected target database import to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('previous database and uploaded files were restored', $exception->getMessage());
        }

        $this->assertSame(['target-state', 'pre-restore-state'], $service->importedDumps);
        $this->assertFileExists($publicPath.DIRECTORY_SEPARATOR.'keep.txt');
        $this->assertSame('before restore', file_get_contents($publicPath.DIRECTORY_SEPARATOR.'keep.txt'));
        $this->assertFileDoesNotExist($publicPath.DIRECTORY_SEPARATOR.'replacement.txt');
        $this->assertCount(1, glob($root.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'pre_restore_*.zip'));
    }

    public function test_retention_prunes_regular_backups_but_keeps_pre_restore_archives(): void
    {
        $root = $this->tempRoot();
        $backupPath = $root.DIRECTORY_SEPARATOR.'backups';
        mkdir($backupPath, 0777, true);
        BackupSetting::query()->first()->update(['retention_count' => 2]);

        foreach (['backup_1.zip', 'backup_2.zip', 'backup_3.zip', 'pre_restore_1.zip', 'pre_restore_2.zip'] as $index => $filename) {
            file_put_contents($backupPath.DIRECTORY_SEPARATOR.$filename, 'archive');
            touch($backupPath.DIRECTORY_SEPARATOR.$filename, time() + $index);
        }

        (new TestableBackupService($root))->pruneOldBackups();

        $remaining = array_map('basename', glob($backupPath.DIRECTORY_SEPARATOR.'*.zip'));
        sort($remaining);
        $this->assertSame(['backup_2.zip', 'backup_3.zip', 'pre_restore_1.zip', 'pre_restore_2.zip'], $remaining);
    }

    private function tempRoot(): string
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'orgtrack-backup-test-'.bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        $this->temporaryRoots[] = $root;

        return $root;
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryRoots as $root) {
            $this->deleteTemporaryDirectory($root);
        }

        parent::tearDown();
    }

    private function deleteTemporaryDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        foreach (scandir($directory) ?: [] as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }
            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteTemporaryDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }

    private function createArchive(string $path, string $sql, string $filename, string $contents): void
    {
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database.sql', $sql);
        $zip->addEmptyDir('storage/public');
        $zip->addFromString('storage/public/'.$filename, $contents);
        $zip->close();
    }
}

class TestableBackupService extends BackupService
{
    public array $importedDumps = [];

    public function __construct(private readonly string $root)
    {
        $this->backupDirectory = 'backups';
    }

    protected function backupDirectoryPath(): string
    {
        return $this->root.DIRECTORY_SEPARATOR.'backups';
    }

    protected function publicStoragePath(): string
    {
        return $this->root.DIRECTORY_SEPARATOR.'public';
    }

    protected function dumpDatabase(string $destination): void
    {
        file_put_contents($destination, 'pre-restore-state');
    }

    protected function importDatabase(string $sqlFile): void
    {
        $dump = file_get_contents($sqlFile);
        $this->importedDumps[] = $dump;
        if ($dump === 'target-state') {
            throw new \RuntimeException('simulated target import failure');
        }
    }
}
