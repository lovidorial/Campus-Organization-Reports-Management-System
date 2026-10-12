<?php

namespace Tests\Unit;

use App\Models\BackupSetting;
use App\Services\BackupService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PDO;
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
        $this->assertNotFalse($zip->locateName('storage/private/'));
        $zip->close();
    }

    public function test_restore_failure_reimports_pre_restore_dump_and_restores_files(): void
    {
        $root = $this->tempRoot();
        $publicPath = $root.DIRECTORY_SEPARATOR.'public';
        mkdir($publicPath, 0777, true);
        file_put_contents($publicPath.DIRECTORY_SEPARATOR.'keep.txt', 'before restore');
        $privatePath = $root.DIRECTORY_SEPARATOR.'private';
        mkdir($privatePath, 0700, true);
        file_put_contents($privatePath.DIRECTORY_SEPARATOR.'keep-private.txt', 'before restore private');

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
        $this->assertFileExists($privatePath.DIRECTORY_SEPARATOR.'keep-private.txt');
        $this->assertSame('before restore private', file_get_contents($privatePath.DIRECTORY_SEPARATOR.'keep-private.txt'));
        $this->assertCount(1, glob($root.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'pre_restore_*.zip'));
    }

    public function test_successful_restore_replaces_private_files_from_the_archive(): void
    {
        $root = $this->tempRoot();
        $publicPath = $root.DIRECTORY_SEPARATOR.'public';
        $privatePath = $root.DIRECTORY_SEPARATOR.'private';
        mkdir($publicPath, 0777, true);
        mkdir($privatePath, 0700, true);
        file_put_contents($publicPath.DIRECTORY_SEPARATOR.'old.txt', 'old public');
        file_put_contents($privatePath.DIRECTORY_SEPARATOR.'old.txt', 'old private');

        $targetArchive = $root.DIRECTORY_SEPARATOR.'target-with-private.zip';
        $this->createArchive($targetArchive, 'target-state', 'new.txt', 'new public', 'private.txt', 'new private');

        $this->assertTrue((new TestableBackupService($root, false))->restoreBackup($targetArchive));
        $this->assertFileDoesNotExist($publicPath.DIRECTORY_SEPARATOR.'old.txt');
        $this->assertSame('new public', file_get_contents($publicPath.DIRECTORY_SEPARATOR.'new.txt'));
        $this->assertFileDoesNotExist($privatePath.DIRECTORY_SEPARATOR.'old.txt');
        $this->assertSame('new private', file_get_contents($privatePath.DIRECTORY_SEPARATOR.'private.txt'));
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

    public function test_dump_uses_pdo_fallback_when_process_functions_are_unavailable(): void
    {
        $root = $this->tempRoot();
        $pdo = $this->pdoWithDumpFixture();
        $service = new PdoDumpTestService($root, $pdo, false);
        $destination = $root.DIRECTORY_SEPARATOR.'fallback.sql';

        $service->writeDatabaseDump($destination);

        $this->assertFileExists($destination);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', file_get_contents($destination));
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=1;', file_get_contents($destination));
        $this->assertSame(0, $service->legacyProcessCalls);
    }

    public function test_dump_uses_the_legacy_mysqldump_path_when_process_functions_are_available(): void
    {
        $root = $this->tempRoot();
        $service = new PdoDumpTestService($root, $this->pdoWithDumpFixture(), true);
        $destination = $root.DIRECTORY_SEPARATOR.'legacy.sql';

        $service->writeDatabaseDump($destination);

        $this->assertSame(1, $service->legacyProcessCalls);
        $this->assertFileExists($destination);
        $this->assertStringContainsString('--databases', implode(' ', $service->legacyCommand));
    }

    public function test_pdo_dump_escapes_null_binary_and_json_values(): void
    {
        $root = $this->tempRoot();
        $service = new PdoDumpTestService($root, $this->pdoWithDumpFixture(), false);
        $destination = $root.DIRECTORY_SEPARATOR.'values.sql';

        $service->writeDatabaseDump($destination);
        $dump = file_get_contents($destination);

        $this->assertStringContainsString('NULL', $dump);
        $this->assertStringContainsString('0x00ff', $dump);
        $this->assertStringContainsString("{\"ok\":true,\"text\":\"it''s valid\"}", $dump);
        $this->assertStringNotContainsString('CREATE DATABASE', strtoupper($dump));
        $this->assertStringNotContainsString('USE `', strtoupper($dump));
    }

    public function test_pdo_dump_fetches_large_tables_in_bounded_batches(): void
    {
        $root = $this->tempRoot();
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE sample (id INTEGER, note TEXT)');
        $insert = $pdo->prepare('INSERT INTO sample (id, note) VALUES (?, ?)');
        for ($index = 1; $index <= 205; $index++) {
            $insert->execute([$index, 'row-'.$index]);
        }

        $service = new PdoDumpTestService($root, $pdo, false);
        $destination = $root.DIRECTORY_SEPARATOR.'large.sql';
        $service->writeDatabaseDump($destination);

        $this->assertSame(3, $service->batchFetchCalls);
        $this->assertSame(205, substr_count(file_get_contents($destination), 'INSERT INTO `sample`'));
    }

    public function test_failed_pdo_dump_removes_partially_written_destination(): void
    {
        $root = $this->tempRoot();
        $service = new PdoDumpTestService($root, $this->pdoWithDumpFixture(), false, true);
        $destination = $root.DIRECTORY_SEPARATOR.'partial.sql';

        try {
            $service->writeDatabaseDump($destination);
            $this->fail('Expected the simulated dump failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database backup could not run on this server.', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($destination);
    }

    public function test_failed_backup_removes_partial_dump_and_archive(): void
    {
        $root = $this->tempRoot();
        $service = new FailingDumpBackupService($root);

        try {
            $service->createBackup();
            $this->fail('Expected the simulated backup failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database backup could not run on this server.', $exception->getMessage());
        }

        $this->assertSame([], glob($root.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'*.zip'));
        $this->assertSame([], glob($root.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'database_*.sql'));
    }

    public function test_web_trigger_failure_cooldown_expires_at_six_hours(): void
    {
        $now = CarbonImmutable::parse('2026-10-12 12:00:00');
        $service = new BackupService;

        try {
            Cache::put('backup:web-trigger:last-failure-at', $now->getTimestamp(), 21600);

            $this->assertTrue($service->isWebTriggeredBackupCoolingDown($now->addHours(5)->addMinutes(59)->addSeconds(59)));
            $this->assertFalse($service->isWebTriggeredBackupCoolingDown($now->addHours(6)));
        } finally {
            $service->clearWebTriggeredBackupFailure();
        }
    }

    private function pdoWithDumpFixture(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE sample (id INTEGER, nullable_value TEXT, binary_value BLOB, json_value JSON)');
        $statement = $pdo->prepare('INSERT INTO sample VALUES (?, ?, ?, ?)');
        $statement->execute([1, null, "\0\xff", '{"ok":true,"text":"it\'s valid"}']);

        return $pdo;
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

    private function createArchive(
        string $path,
        string $sql,
        string $filename,
        string $contents,
        ?string $privateFilename = null,
        string $privateContents = ''
    ): void
    {
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database.sql', $sql);
        $zip->addEmptyDir('storage/public');
        $zip->addFromString('storage/public/'.$filename, $contents);
        $zip->addEmptyDir('storage/private');
        if ($privateFilename) {
            $zip->addFromString('storage/private/'.$privateFilename, $privateContents);
        }
        $zip->close();
    }
}

class TestableBackupService extends BackupService
{
    public array $importedDumps = [];

    public function __construct(private readonly string $root, private readonly bool $failTargetImport = true)
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

    protected function privateStoragePath(): string
    {
        return $this->root.DIRECTORY_SEPARATOR.'private';
    }

    protected function dumpDatabase(string $destination): void
    {
        file_put_contents($destination, 'pre-restore-state');
    }

    protected function importDatabase(string $sqlFile): void
    {
        $dump = file_get_contents($sqlFile);
        $this->importedDumps[] = $dump;
        if ($dump === 'target-state' && $this->failTargetImport) {
            throw new \RuntimeException('simulated target import failure');
        }
    }
}

class PdoDumpTestService extends BackupService
{
    public int $legacyProcessCalls = 0;

    public int $batchFetchCalls = 0;

    public array $legacyCommand = [];

    public function __construct(
        private readonly string $root,
        private readonly PDO $pdo,
        private readonly bool $processIsAvailable,
        private readonly bool $failColumnListing = false
    ) {
        $this->backupDirectory = 'backups';
    }

    public function writeDatabaseDump(string $destination): void
    {
        $this->dumpDatabase($destination);
    }

    protected function processAvailable(): bool
    {
        return $this->processIsAvailable;
    }

    protected function runMysqldumpProcess(array $command, array $environment, string $destination): void
    {
        $this->legacyProcessCalls++;
        $this->legacyCommand = $command;
        file_put_contents($destination, 'legacy-dump');
    }

    protected function pdoForDatabaseDump(): PDO
    {
        return $this->pdo;
    }

    protected function isMysqlDumpPdo(PDO $pdo): bool
    {
        return true;
    }

    protected function listDumpTables(PDO $pdo): array
    {
        return ['sample'];
    }

    protected function createDumpTableStatement(PDO $pdo, string $table): string
    {
        return 'CREATE TABLE `sample` (`id` INTEGER, `nullable_value` TEXT, `binary_value` BLOB, `json_value` JSON)';
    }

    protected function listDumpColumns(PDO $pdo, string $table): array
    {
        if ($this->failColumnListing) {
            throw new \RuntimeException('simulated metadata query failure');
        }

        $statement = $pdo->query('PRAGMA table_info(`'.str_replace('`', '``', $table).'`)');
        $columns = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = ['name' => $column['name'], 'type' => $column['type']];
        }

        return $columns;
    }

    protected function fetchDumpBatch(PDO $pdo, string $table, int $offset, int $limit): array
    {
        $this->batchFetchCalls++;

        return parent::fetchDumpBatch($pdo, $table, $offset, $limit);
    }
}

class FailingDumpBackupService extends BackupService
{
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

    protected function privateStoragePath(): string
    {
        return $this->root.DIRECTORY_SEPARATOR.'private';
    }

    protected function dumpDatabase(string $destination): void
    {
        file_put_contents($destination, 'partial dump');

        throw new \RuntimeException('Database backup could not run on this server.');
    }
}
