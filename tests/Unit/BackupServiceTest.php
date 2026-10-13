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

    public function test_create_backup_continues_when_execution_limit_functions_are_unavailable(): void
    {
        $root = $this->tempRoot();
        $service = new ExecutionFunctionsUnavailableBackupService($root);

        $result = $service->createBackup();

        $this->assertFileExists($result['path']);
        $this->assertSame(['set_time_limit', 'ignore_user_abort'], $service->checkedFunctions);
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

    public function test_sql_parser_splits_quoted_semicolons_and_handles_escapes_and_comments(): void
    {
        $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'));
        $sqlFile = $this->writeSqlFile(<<<'SQL'
-- Header comment; ignored
SET @single = 'one; two';
SET @double = "three; four";
CREATE TABLE `semi;table` (`back``tick` TEXT, `note` TEXT);
INSERT INTO `semi;table` VALUES ('escaped \' quote; still string', 'doubled '' quote; still string');
# Hash comment; ignored
/* ordinary block comment; ignored */ SET @after_comments = 1;
SQL);

        $statements = $service->parseSqlFile($sqlFile);

        $this->assertCount(5, $statements);
        $this->assertStringContainsString("'one; two'", $statements[0]);
        $this->assertStringContainsString('"three; four"', $statements[1]);
        $this->assertStringContainsString('`semi;table`', $statements[2]);
        $this->assertStringContainsString("escaped \\' quote; still string", $statements[3]);
        $this->assertStringContainsString("doubled '' quote; still string", $statements[3]);
        $this->assertStringNotContainsString('ordinary block comment', $statements[4]);
    }

    public function test_mysqldump_header_and_conditional_comments_are_preserved_while_database_and_lock_statements_are_skipped(): void
    {
        $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'));
        $sqlFile = $this->writeSqlFile(<<<'SQL'
-- MySQL dump 10.13  Distrib 8.0, for Linux
/*!40101 SET NAMES utf8mb4 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `orgtrack` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE /*!40100 */ `orgtrack`;
/*M!100100 SET @mariadb_compat = 1 */;
DROP TABLE IF EXISTS `sample`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
CREATE TABLE `sample` (`id` int NOT NULL, `note` text) ENGINE=InnoDB;
LOCK TABLES `sample` WRITE;
INSERT INTO `sample` VALUES (1,'semi;colon and it\'s quoted');
UNLOCK TABLES;
SQL);

        $service->importSqlFile($sqlFile);

        $this->assertContains('/*!40101 SET NAMES utf8mb4 */', $service->executedStatements);
        $this->assertContains('/*M!100100 SET @mariadb_compat = 1 */', $service->executedStatements);
        $this->assertNotContains('CREATE DATABASE /*!32312 IF NOT EXISTS*/ `orgtrack` /*!40100 DEFAULT CHARACTER SET utf8mb4 */', $service->executedStatements);
        $this->assertNotContains('USE /*!40100 */ `orgtrack`', $service->executedStatements);
        $this->assertNotContains('LOCK TABLES `sample` WRITE', $service->executedStatements);
        $this->assertNotContains('UNLOCK TABLES', $service->executedStatements);
        $this->assertContains('CREATE TABLE `sample` (`id` int NOT NULL, `note` text) ENGINE=InnoDB', $service->executedStatements);
    }

    public function test_pdo_dump_fallback_format_is_accepted_by_import_parser(): void
    {
        $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'));
        $sqlFile = $this->writeSqlFile(<<<'SQL'
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `sample`;
CREATE TABLE `sample` (`id` int, `note` text);

INSERT INTO `sample` (`id`, `note`) VALUES (1, 'fallback; row');

SET FOREIGN_KEY_CHECKS=1;
SQL);

        $service->importSqlFile($sqlFile);

        $this->assertContains('DROP TABLE IF EXISTS `sample`', $service->executedStatements);
        $this->assertContains("INSERT INTO `sample` (`id`, `note`) VALUES (1, 'fallback; row')", $service->executedStatements);
    }

    public function test_entire_file_is_validated_before_any_statement_executes(): void
    {
        $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'));
        $sqlFile = $this->writeSqlFile("SET @safe = 1;\nINSERT INTO sample VALUES ('unterminated);\n");

        try {
            $service->importSqlFile($sqlFile);
            $this->fail('Expected parser to reject an unterminated string.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('unterminated', strtolower($exception->getMessage()));
        }

        $this->assertSame([], $service->executedStatements);
        $this->assertSame([], $service->foreignKeyStates);
    }

    public function test_unterminated_backtick_and_block_comment_fail_during_validation(): void
    {
        foreach (["SELECT `unterminated;", 'SELECT 1; /* unterminated'] as $sql) {
            $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'));
            $sqlFile = $this->writeSqlFile($sql);

            try {
                $service->importSqlFile($sqlFile);
                $this->fail('Expected malformed quoting/comment syntax to be rejected.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('unterminated', strtolower($exception->getMessage()));
            }

            $this->assertSame([], $service->executedStatements);
            $this->assertSame([], $service->foreignKeyStates);
        }
    }

    public function test_unsupported_delimiter_trigger_routine_and_event_are_rejected_before_execution(): void
    {
        $unsupportedSql = [
            "DELIMITER $$\nCREATE PROCEDURE p() SELECT 1$$\nDELIMITER ;",
            'CREATE TRIGGER tr BEFORE INSERT ON sample FOR EACH ROW SET @a = 1;',
            'CREATE PROCEDURE p() SELECT 1;',
            'CREATE FUNCTION f() RETURNS INT RETURN 1;',
            'CREATE EVENT e ON SCHEDULE EVERY 1 DAY DO SELECT 1;',
        ];

        foreach ($unsupportedSql as $sql) {
            $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'));
            $sqlFile = $this->writeSqlFile("SET @safe = 1;\n".$sql);

            try {
                $service->importSqlFile($sqlFile);
                $this->fail('Expected unsupported SQL to be rejected.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('unsupported', strtolower($exception->getMessage()));
            }

            $this->assertSame([], $service->executedStatements);
            $this->assertSame([], $service->foreignKeyStates);
        }
    }

    public function test_import_execution_failure_restores_foreign_key_checks_and_redacts_values(): void
    {
        $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'));
        $service->failStatementContaining = 'private-secret-value';
        $sqlFile = $this->writeSqlFile("INSERT INTO `users` (`password`) VALUES ('private-secret-value');");

        try {
            $service->importSqlFile($sqlFile);
            $this->fail('Expected the simulated statement error.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('statement 1', $exception->getMessage());
            $this->assertStringNotContainsString('private-secret-value', $exception->getMessage());
            $this->assertStringContainsString('[redacted]', $exception->getMessage());
        }

        $this->assertSame([false, true], $service->foreignKeyStates);
    }

    public function test_process_mysql_import_path_is_used_when_proc_open_is_available(): void
    {
        $service = new PdoImportTestService($this->tempRoot(), new PDO('sqlite::memory:'), true);
        $sqlFile = $this->writeSqlFile('SELECT 1;');

        $service->importSqlFile($sqlFile);

        $this->assertSame(1, $service->legacyImportCalls);
        $this->assertSame([], $service->executedStatements);
    }

    public function test_restore_capability_guard_runs_before_pre_restore_backup_creation(): void
    {
        $root = $this->tempRoot();
        $targetArchive = $root.DIRECTORY_SEPARATOR.'target.zip';
        $this->createArchive($targetArchive, 'SELECT 1;', 'target.txt', 'target');
        $service = new UnavailableImportTestService($root, new PDO('sqlite::memory:'));

        try {
            $service->restoreFrom($targetArchive);
            $this->fail('Expected restore to be rejected before staging.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Restore is not available on this server', $exception->getMessage());
        }

        $this->assertSame(0, $service->preRestoreBackupCalls);
    }

    public function test_failed_target_import_uses_pdo_fallback_to_import_pre_restore_backup(): void
    {
        $root = $this->tempRoot();
        $targetArchive = $root.DIRECTORY_SEPARATOR.'target-failure.zip';
        $this->createArchive($targetArchive, "INSERT INTO `sample` VALUES ('target-failure');", 'target.txt', 'target');
        $service = new PdoRollbackRestoreTestService($root, new PDO('sqlite::memory:'));
        $service->failStatementContaining = 'target-failure';

        try {
            $service->restoreFrom($targetArchive);
            $this->fail('Expected target SQL import to fail and rollback to run.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('previous database and uploaded files were restored', $exception->getMessage());
        }

        $this->assertContains("INSERT INTO `sample` VALUES ('rollback-ok')", $service->executedStatements);
        $this->assertSame([false, true, false, true], $service->foreignKeyStates);
        $this->assertSame(1, $service->preRestoreBackupCalls);
    }

    private function pdoWithDumpFixture(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE sample (id INTEGER, nullable_value TEXT, binary_value BLOB, json_value JSON)');
        $statement = $pdo->prepare('INSERT INTO sample VALUES (?, ?, ?, ?)');
        $statement->execute([1, null, "\0\xff", '{"ok":true,"text":"it\'s valid"}']);

        return $pdo;
    }

    private function writeSqlFile(string $sql): string
    {
        $path = $this->tempRoot().DIRECTORY_SEPARATOR.'fixture.sql';
        file_put_contents($path, $sql);

        return $path;
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

class ExecutionFunctionsUnavailableBackupService extends TestableBackupService
{
    public array $checkedFunctions = [];

    protected function executionFunctionAvailable(string $function): bool
    {
        $this->checkedFunctions[] = $function;

        return false;
    }
}

class PdoImportTestService extends BackupService
{
    public array $executedStatements = [];

    public array $foreignKeyStates = [];

    public int $legacyImportCalls = 0;

    public ?string $failStatementContaining = null;

    public function __construct(
        protected readonly string $root,
        protected readonly PDO $pdo,
        private readonly bool $processIsAvailable = false
    ) {
        $this->backupDirectory = 'backups';
    }

    public function parseSqlFile(string $sqlFile): array
    {
        return iterator_to_array($this->readPdoImportStatements($sqlFile), false);
    }

    public function importSqlFile(string $sqlFile): void
    {
        $this->importDatabase($sqlFile);
    }

    public function restoreFrom(string $archivePath): bool
    {
        return $this->performRestore($archivePath);
    }

    protected function processAvailable(): bool
    {
        return $this->processIsAvailable;
    }

    protected function canImportDatabase(): bool
    {
        return true;
    }

    protected function pdoForDatabaseImport(): PDO
    {
        return $this->pdo;
    }

    protected function isMysqlImportPdo(PDO $pdo): bool
    {
        return true;
    }

    protected function setImportForeignKeyChecks(PDO $pdo, bool $enabled): void
    {
        $this->foreignKeyStates[] = $enabled;
    }

    protected function executePdoImportStatement(PDO $pdo, string $statement): void
    {
        $this->executedStatements[] = $statement;
        if ($this->failStatementContaining !== null && str_contains($statement, $this->failStatementContaining)) {
            throw new \RuntimeException('simulated SQL failure with sensitive database values');
        }
    }

    protected function runMysqlImportProcess(array $command, array $environment, string $sqlFile): void
    {
        $this->legacyImportCalls++;
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
}

class UnavailableImportTestService extends PdoImportTestService
{
    public int $preRestoreBackupCalls = 0;

    protected function canImportDatabase(): bool
    {
        return false;
    }

    public function createBackup(string $prefix = 'backup_', bool $recordStatus = true): array
    {
        $this->preRestoreBackupCalls++;

        throw new \RuntimeException('Pre-restore backup should not be reached.');
    }
}

class PdoRollbackRestoreTestService extends PdoImportTestService
{
    public int $preRestoreBackupCalls = 0;

    public function createBackup(string $prefix = 'backup_', bool $recordStatus = true): array
    {
        $this->preRestoreBackupCalls++;
        $directory = $this->backupDirectoryPath();
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        $filename = 'pre_restore_test.zip';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database.sql', "INSERT INTO `sample` VALUES ('rollback-ok');");
        $zip->addEmptyDir('storage/public');
        $zip->addEmptyDir('storage/private');
        $zip->close();

        return ['path' => $path, 'filename' => $filename, 'size' => filesize($path)];
    }
}
