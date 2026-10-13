<?php

namespace App\Services;

use App\Models\BackupArchive;
use App\Models\BackupSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PDO;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    private const WEB_TRIGGER_FAILURE_CACHE_KEY = 'backup:web-trigger:last-failure-at';

    private const WEB_TRIGGER_FAILURE_COOLDOWN_SECONDS = 21600;

    private const DATABASE_DUMP_BATCH_SIZE = 100;

    protected string $backupDirectory = 'backups';

    public function isWebTriggeredBackupCoolingDown(?CarbonInterface $asOf = null): bool
    {
        $failedAt = Cache::get(self::WEB_TRIGGER_FAILURE_CACHE_KEY);
        if (! is_numeric($failedAt)) {
            return false;
        }

        $now = $asOf ? Carbon::instance($asOf)->getTimestamp() : now()->getTimestamp();

        return (int) $failedAt > $now - self::WEB_TRIGGER_FAILURE_COOLDOWN_SECONDS;
    }

    public function recordWebTriggeredBackupFailure(): void
    {
        Cache::put(
            self::WEB_TRIGGER_FAILURE_CACHE_KEY,
            now()->getTimestamp(),
            self::WEB_TRIGGER_FAILURE_COOLDOWN_SECONDS
        );
    }

    public function clearWebTriggeredBackupFailure(): void
    {
        Cache::forget(self::WEB_TRIGGER_FAILURE_CACHE_KEY);
    }

    public function isBackupDue(BackupSetting $settings, ?CarbonInterface $asOf = null): bool
    {
        $frequency = $settings->frequency;
        if ($frequency === 'manual') {
            return false;
        }

        $now = $asOf ? Carbon::instance($asOf) : now();
        $lastRunAt = $settings->last_successful_at ?? $settings->last_run_at;
        if (! $lastRunAt) {
            return true;
        }

        return match ($frequency) {
            'monthly' => Carbon::instance($lastRunAt)->addDays(30)->lte($now),
            'per_semester' => $this->semesterKey($lastRunAt) !== $this->semesterKey($now),
            'per_school_year' => $this->schoolYearKey($lastRunAt) !== $this->schoolYearKey($now),
            default => false,
        };
    }

    public function nextBackupDueAt(BackupSetting $settings, ?CarbonInterface $asOf = null): ?Carbon
    {
        if ($settings->frequency === 'manual') {
            return null;
        }

        $now = $asOf ? Carbon::instance($asOf) : now();
        $lastRunAt = $settings->last_successful_at ?? $settings->last_run_at;
        if (! $lastRunAt) {
            return $now;
        }

        $lastRunAt = Carbon::instance($lastRunAt);

        return match ($settings->frequency) {
            'monthly' => $lastRunAt->addDays(30),
            'per_semester' => $this->semesterKey($lastRunAt) === $this->semesterKey($now)
                ? $this->nextSemesterStart($now)
                : $now,
            'per_school_year' => $this->schoolYearKey($lastRunAt) === $this->schoolYearKey($now)
                ? $this->nextSchoolYearStart($now)
                : $now,
            default => null,
        };
    }

    private function semesterKey(CarbonInterface $date): string
    {
        return $date->format('Y').'-'.((int) $date->format('n') <= 6 ? '1' : '2');
    }

    private function schoolYearKey(CarbonInterface $date): int
    {
        return (int) $date->format('Y') - ((int) $date->format('n') < 8 ? 1 : 0);
    }

    private function nextSemesterStart(Carbon $date): Carbon
    {
        $month = (int) $date->format('n');

        return $date->copy()->setDate((int) $date->format('Y') + ($month > 6 ? 1 : 0), $month <= 6 ? 7 : 1, 1)->startOfDay();
    }

    private function nextSchoolYearStart(Carbon $date): Carbon
    {
        return $date->copy()->setDate((int) $date->format('Y') + ((int) $date->format('n') >= 8 ? 1 : 0), 8, 1)->startOfDay();
    }

    public function createBackup(string $prefix = 'backup_', bool $recordStatus = true): array
    {
        $backupPath = $this->backupDirectoryPath();
        if (! is_dir($backupPath) && ! mkdir($backupPath, 0777, true) && ! is_dir($backupPath)) {
            throw new \RuntimeException('Unable to create backup directory: '.$backupPath);
        }

        $timestamp = now()->format('Y-m-d_His');
        $fileName = $prefix.$timestamp.'.zip';
        $fullPath = $backupPath.DIRECTORY_SEPARATOR.$fileName;
        $dbDumpPath = $backupPath.DIRECTORY_SEPARATOR.'database_'.uniqid('', true).'.sql';
        $zip = new ZipArchive;

        try {
            $storagePublicPath = $this->publicStoragePath();
            $storagePrivatePath = $this->privateStoragePath();
            if (! is_dir($storagePublicPath) && ! mkdir($storagePublicPath, 0777, true) && ! is_dir($storagePublicPath)) {
                throw new \RuntimeException('Unable to create public storage directory for backup: '.$storagePublicPath);
            }
            if (! is_dir($storagePrivatePath) && ! mkdir($storagePrivatePath, 0700, true) && ! is_dir($storagePrivatePath)) {
                throw new \RuntimeException('Unable to create private storage directory for backup: '.$storagePrivatePath);
            }

            $zipOpenResult = $zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($zipOpenResult !== true) {
                throw new \RuntimeException('Unable to create backup archive: code '.$zipOpenResult);
            }

            $zip->addEmptyDir('storage/public');
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePublicPath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $relativePath = str_replace('\\', '/', ltrim(str_replace($storagePublicPath, '', $file->getPathname()), DIRECTORY_SEPARATOR));
                if (! $zip->addFile($file->getPathname(), 'storage/public/'.ltrim($relativePath, '/'))) {
                    throw new \RuntimeException('Unable to add a public storage file to the backup archive.');
                }
            }

            $zip->addEmptyDir('storage/private');
            $privateFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePrivatePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($privateFiles as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $relativePath = str_replace('\\', '/', ltrim(str_replace($storagePrivatePath, '', $file->getPathname()), DIRECTORY_SEPARATOR));
                if (! $zip->addFile($file->getPathname(), 'storage/private/'.ltrim($relativePath, '/'))) {
                    throw new \RuntimeException('Unable to add a private storage file to the backup archive.');
                }
            }

            $this->dumpDatabase($dbDumpPath);
            if (! is_file($dbDumpPath) || filesize($dbDumpPath) === 0) {
                throw new \RuntimeException('Database backup failed: the SQL dump is empty.');
            }
            if (! $zip->addFile($dbDumpPath, 'database.sql') || ! $zip->close()) {
                throw new \RuntimeException('Unable to finalize the backup archive.');
            }
            $zip = null;

            $size = is_file($fullPath) ? (int) filesize($fullPath) : 0;
            $this->verifyBackupArchive($fullPath, $size);
            if ($recordStatus && ! str_starts_with($prefix, 'pre_restore_')) {
                $this->recordSuccessfulBackup();
            }

            return ['path' => $fullPath, 'filename' => $fileName, 'size' => $size];
        } catch (\Throwable $exception) {
            if ($zip instanceof ZipArchive) {
                @$zip->close();
            }
            @unlink($fullPath);
            if ($recordStatus && ! str_starts_with($prefix, 'pre_restore_')) {
                $this->recordBackupFailure($exception);
            }
            throw $exception;
        } finally {
            @unlink($dbDumpPath);
        }
    }

    public function restoreBackup(string $backupFilePath): bool
    {
        $lock = Cache::lock('orgtrack-backup-restore', 900);
        if (! $lock->get()) {
            throw new \RuntimeException('Another backup restore is already in progress. Please try again later.');
        }

        try {
            return $this->performRestore($backupFilePath);
        } finally {
            $lock->release();
        }
    }

    protected function performRestore(string $backupFilePath): bool
    {
        $resolvedPath = realpath($backupFilePath);
        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            throw new \RuntimeException('The selected backup file could not be found.');
        }

        if (! $this->canImportDatabase()) {
            throw new \RuntimeException('Restore is not available on this server');
        }

        $publicStorage = $this->publicStoragePath();
        $privateStorage = $this->privateStoragePath();
        if (is_link($publicStorage)) {
            throw new \RuntimeException('Cannot restore while storage/app/public is a symlink or junction. Replace it with a regular directory or restore its target separately.');
        }
        if (! is_dir($publicStorage) && ! mkdir($publicStorage, 0777, true) && ! is_dir($publicStorage)) {
            throw new \RuntimeException('storage/app/public is not writable — check folder permissions');
        }
        $this->checkPublicStorageWritable($publicStorage);
        if (is_link($privateStorage)) {
            throw new \RuntimeException('Cannot restore while storage/app/private is a symlink or junction. Replace it with a regular directory or restore its target separately.');
        }
        if (! is_dir($privateStorage) && ! mkdir($privateStorage, 0700, true) && ! is_dir($privateStorage)) {
            throw new \RuntimeException('storage/app/private is not writable — check folder permissions');
        }
        $this->checkStorageWritable($privateStorage, 'storage/app/private');

        $workDirectory = dirname($publicStorage).DIRECTORY_SEPARATOR.'.restore-'.bin2hex(random_bytes(8));
        if (! mkdir($workDirectory, 0700, true) && ! is_dir($workDirectory)) {
            throw new \RuntimeException('Unable to create a temporary restore workspace.');
        }

        $publicParent = dirname($publicStorage);
        $oldPublicStorage = $publicParent.DIRECTORY_SEPARATOR.'.public-before-restore-'.bin2hex(random_bytes(8));
        $stagedPublicStorage = $publicParent.DIRECTORY_SEPARATOR.'.public-restored-'.bin2hex(random_bytes(8));
        $oldPrivateStorage = $publicParent.DIRECTORY_SEPARATOR.'.private-before-restore-'.bin2hex(random_bytes(8));
        $stagedPrivateStorage = $publicParent.DIRECTORY_SEPARATOR.'.private-restored-'.bin2hex(random_bytes(8));
        $previousPublicCopied = false;
        $targetFilesInstalled = false;
        $previousPrivateCopied = false;
        $targetPrivateFilesInstalled = false;
        $targetImportAttempted = false;
        $preRestore = null;

        try {
            $targetExtract = $workDirectory.DIRECTORY_SEPARATOR.'target';
            $this->extractAndValidateArchive($resolvedPath, $targetExtract);

            $preRestore = $this->createBackup('pre_restore_', false);
            $rollbackExtract = $workDirectory.DIRECTORY_SEPARATOR.'rollback';
            $this->extractAndValidateArchive($preRestore['path'], $rollbackExtract);
            $this->copyDirectoryForRestore(
                $targetExtract.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'public',
                $stagedPublicStorage
            );
            $targetPrivatePath = $targetExtract.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'private';
            $replacePrivateStorage = is_dir($targetPrivatePath);
            if ($replacePrivateStorage) {
                $this->copyDirectoryForRestore($targetPrivatePath, $stagedPrivateStorage);
            }

            $targetImportAttempted = true;
            $this->importDatabase($targetExtract.DIRECTORY_SEPARATOR.'database.sql');

            $this->copyDirectoryForRestore($publicStorage, $oldPublicStorage);
            $previousPublicCopied = true;
            $this->deleteDirectoryForRestore($publicStorage);

            $this->copyDirectoryForRestore($stagedPublicStorage, $publicStorage);
            $targetFilesInstalled = true;

            if ($replacePrivateStorage) {
                $this->copyDirectoryForRestore($privateStorage, $oldPrivateStorage);
                $previousPrivateCopied = true;
                $this->deleteDirectoryForRestore($privateStorage);
                $this->copyDirectoryForRestore($stagedPrivateStorage, $privateStorage);
                $targetPrivateFilesInstalled = true;
            }

            foreach ([$oldPublicStorage, $oldPrivateStorage] as $oldStorage) {
                try {
                    $this->deleteDirectoryForRestore($oldStorage);
                } catch (\Throwable $cleanupException) {
                    Log::warning('Backup restore completed but could not remove a temporary storage copy.', [
                        'path' => $oldStorage,
                        'exception' => $cleanupException->getMessage(),
                    ]);
                }
            }

            return true;
        } catch (\Throwable $exception) {
            if ($targetImportAttempted) {
                try {
                    $this->importDatabase($workDirectory.DIRECTORY_SEPARATOR.'rollback'.DIRECTORY_SEPARATOR.'database.sql');

                    if ($previousPublicCopied && is_dir($oldPublicStorage)) {
                        if (is_link($publicStorage)) {
                            throw new \RuntimeException('Refusing to delete storage/app/public during rollback because it became a symlink or junction.');
                        }
                        if (is_dir($publicStorage)) {
                            $this->deleteDirectoryForRestore($publicStorage);
                        }
                        $this->copyDirectoryForRestore($oldPublicStorage, $publicStorage);
                        $this->deleteDirectoryForRestore($oldPublicStorage);
                        $previousPublicCopied = false;
                    } elseif ($targetFilesInstalled) {
                        $this->deleteDirectoryForRestore($publicStorage);
                    }

                    if ($previousPrivateCopied && is_dir($oldPrivateStorage)) {
                        if (is_link($privateStorage)) {
                            throw new \RuntimeException('Refusing to delete storage/app/private during rollback because it became a symlink or junction.');
                        }
                        if (is_dir($privateStorage)) {
                            $this->deleteDirectoryForRestore($privateStorage);
                        }
                        $this->copyDirectoryForRestore($oldPrivateStorage, $privateStorage);
                        $this->deleteDirectoryForRestore($oldPrivateStorage);
                        $previousPrivateCopied = false;
                    } elseif ($targetPrivateFilesInstalled) {
                        $this->deleteDirectoryForRestore($privateStorage);
                    }
                } catch (\Throwable $rollbackException) {
                    Log::critical('Backup restore rollback failed.', [
                        'restore_error' => $exception->getMessage(),
                        'rollback_error' => $rollbackException->getMessage(),
                        'backup_file' => basename($resolvedPath),
                    ]);

                    throw new \RuntimeException(
                        'Restore failed and automatic rollback was not fully successful. Keep the pre-restore backup '
                        .($preRestore['filename'] ?? 'in the backups directory').'. Restore error: '
                        .$exception->getMessage().' Rollback error: '.$rollbackException->getMessage(),
                        0,
                        $exception
                    );
                }
            }

            if (! $targetImportAttempted) {
                throw $exception;
            }

            throw new \RuntimeException(
                'Restore failed; the previous database and uploaded files were restored. '.$exception->getMessage(),
                0,
                $exception
            );
        } finally {
            if (is_dir($stagedPublicStorage) && ! is_link($stagedPublicStorage)) {
                $this->deleteDirectoryForRestore($stagedPublicStorage);
            }
            if (is_dir($stagedPrivateStorage) && ! is_link($stagedPrivateStorage)) {
                $this->deleteDirectoryForRestore($stagedPrivateStorage);
            }
            if (is_dir($workDirectory)) {
                $this->deleteDirectory($workDirectory);
            }
        }
    }

    public function pruneOldBackups(): void
    {
        if (! Schema::hasTable('backup_settings')) {
            return;
        }

        $settings = BackupSetting::query()->first();
        $retentionCount = $settings?->retention_count ?? 5;

        $files = array_values(array_filter(
            $this->listBackupFiles(),
            fn (array $file): bool => str_starts_with($file['name'], 'backup_')
        ));
        if (count($files) <= $retentionCount) {
            return;
        }

        usort($files, fn ($a, $b) => $a['created_at'] <=> $b['created_at']);

        $filesToDelete = array_slice($files, 0, count($files) - $retentionCount);
        foreach ($filesToDelete as $file) {
            if (@unlink($file['path'])) {
                if (Schema::hasTable('backup_archives')) {
                    BackupArchive::query()->where('filename', $file['name'])->delete();
                }
            }
        }
    }

    public function listBackupFiles(): array
    {
        $dir = $this->backupDirectoryPath();
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*.zip') as $file) {
            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'created_at' => filemtime($file),
            ];
        }

        usort($files, fn ($a, $b) => $a['created_at'] <=> $b['created_at']);

        return $files;
    }

    protected function verifyBackupArchive(string $path, int $size): void
    {
        $zip = new ZipArchive;
        if ($size <= 0 || $zip->open($path) !== true) {
            @unlink($path);
            throw new \RuntimeException('Backup verification failed: the ZIP archive is empty or unreadable.');
        }

        $hasDatabase = $zip->locateName('database.sql') !== false;
        $hasPrivateDirectory = $zip->locateName('storage/private/') !== false;
        $zip->close();
        if (! $hasDatabase || ! $hasPrivateDirectory) {
            @unlink($path);
            throw new \RuntimeException('Backup verification failed: database.sql or storage/private is missing from the ZIP archive.');
        }
    }

    protected function extractAndValidateArchive(string $archivePath, string $destination): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('The selected file is not a readable ZIP archive.');
        }

        $hasDatabase = false;
        $hasPublicDirectory = false;
        $hasPrivateDirectory = false;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($index));
            if ($entry === 'database.sql') {
                $hasDatabase = true;

                continue;
            }
            if ($entry === 'storage/public' || $entry === 'storage/public/') {
                $hasPublicDirectory = true;

                continue;
            }
            if ($entry === 'storage/private' || $entry === 'storage/private/') {
                $hasPrivateDirectory = true;

                continue;
            }
            if (! str_starts_with($entry, 'storage/public/') && ! str_starts_with($entry, 'storage/private/')) {
                $zip->close();
                throw new \RuntimeException('The archive contains a file outside the supported storage directories.');
            }
            if (str_starts_with($entry, 'storage/public/')) {
                $hasPublicDirectory = true;
            } else {
                $hasPrivateDirectory = true;
            }
            if (str_starts_with($entry, '/') || preg_match('/^[A-Za-z]:/', $entry) || preg_match('#(^|/)\.\.(?:/|$)#', $entry)) {
                $zip->close();
                throw new \RuntimeException('The archive contains an unsafe file path.');
            }
        }

        if (! $hasDatabase) {
            $zip->close();
            throw new \RuntimeException('The archive does not contain the required database.sql dump.');
        }
        if (! $hasPublicDirectory) {
            $zip->close();
            throw new \RuntimeException('The archive does not contain a valid storage/public folder.');
        }
        if (! mkdir($destination, 0700, true) && ! is_dir($destination)) {
            $zip->close();
            throw new \RuntimeException('Unable to create a temporary extraction directory.');
        }
        if (! $zip->extractTo($destination)) {
            $zip->close();
            throw new \RuntimeException('Unable to extract the selected backup archive.');
        }
        $zip->close();

        $sqlPath = $destination.DIRECTORY_SEPARATOR.'database.sql';
        $publicPath = $destination.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'public';
        $privatePath = $destination.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'private';
        if (! is_file($sqlPath) || filesize($sqlPath) === 0 || ! is_dir($publicPath)
            || ($hasPrivateDirectory && ! is_dir($privatePath))) {
            throw new \RuntimeException('The archive is missing a non-empty database.sql or a valid storage folder.');
        }
    }

    protected function recordSuccessfulBackup(): void
    {
        try {
            if (Schema::hasTable('backup_settings') && Schema::hasColumn('backup_settings', 'last_successful_at')) {
                BackupSetting::query()->firstOrCreate()->forceFill([
                    'last_successful_at' => now(),
                    'last_error' => null,
                ])->save();
            }
        } catch (\Throwable $exception) {
            Log::warning('Backup archive completed, but its success status could not be saved.', ['exception' => $exception]);
        }
    }

    protected function recordBackupFailure(\Throwable $exception): void
    {
        Log::error('Local backup creation failed.', ['exception' => $exception]);
        try {
            if (Schema::hasTable('backup_settings') && Schema::hasColumn('backup_settings', 'last_error')) {
                BackupSetting::query()->firstOrCreate()->forceFill([
                    'last_error' => $exception->getMessage(),
                ])->save();
            }
        } catch (\Throwable $statusException) {
            Log::warning('Backup failure status could not be saved.', ['exception' => $statusException]);
        }
    }

    protected function dumpDatabase(string $destination): void
    {
        if (! $this->processAvailable()) {
            try {
                $this->dumpDatabaseWithPdo($destination);
            } catch (\Throwable $exception) {
                @unlink($destination);

                throw new \RuntimeException('Database backup could not run on this server.');
            }

            return;
        }

        $config = config('database.connections.mysql');
        $binary = config('backup.mysqldump_path');
        $environment = $this->buildWindowsProcessEnvironment();
        $environment['MYSQL_PWD'] = (string) ($config['password'] ?? '');

        $command = [
            $binary,
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? ''),
            '--databases',
            ($config['database'] ?? ''),
            '--result-file='.$destination,
        ];

        try {
            $this->runMysqldumpProcess($command, $environment, $destination);
        } catch (\Throwable $exception) {
            @unlink($destination);

            throw new \RuntimeException('Database backup could not run on this server.');
        }
    }

    protected function processAvailable(): bool
    {
        $disabledFunctions = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return function_exists('proc_open') && ! in_array('proc_open', $disabledFunctions, true);
    }

    protected function dumpDatabaseWithPdo(string $destination): void
    {
        $pdo = $this->pdoForDatabaseDump();
        if (! $this->isMysqlDumpPdo($pdo)) {
            throw new \RuntimeException('PDO MySQL is required for the database dump fallback.');
        }

        $stream = fopen($destination, 'wb');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open database dump destination.');
        }

        try {
            $this->writeDump($stream, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($this->listDumpTables($pdo) as $table) {
                $quotedTable = $this->quoteDumpIdentifier($table);
                $this->writeDump($stream, 'DROP TABLE IF EXISTS '.$quotedTable.";\n");
                $this->writeDump($stream, $this->createDumpTableStatement($pdo, $table).";\n\n");

                $columns = $this->listDumpColumns($pdo, $table);
                if ($columns === []) {
                    throw new \RuntimeException('Unable to read table columns.');
                }

                $insertColumns = array_values(array_filter(
                    $columns,
                    fn (array $column): bool => ! str_contains(strtoupper($column['extra'] ?? ''), 'GENERATED')
                ));
                if ($insertColumns === []) {
                    continue;
                }

                $quotedColumns = implode(', ', array_map(
                    fn (array $column): string => $this->quoteDumpIdentifier($column['name']),
                    $insertColumns
                ));
                $offset = 0;

                do {
                    $rows = $this->fetchDumpBatch($pdo, $table, $offset, self::DATABASE_DUMP_BATCH_SIZE);
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($insertColumns as $column) {
                            $values[] = $this->dumpSqlValue($pdo, $row[$column['name']] ?? null, $column['type']);
                        }

                        $this->writeDump(
                            $stream,
                            'INSERT INTO '.$quotedTable.' ('.$quotedColumns.') VALUES ('.implode(', ', $values).");\n"
                        );
                    }

                    $offset += count($rows);
                } while (count($rows) === self::DATABASE_DUMP_BATCH_SIZE);

                $this->writeDump($stream, "\n");
            }

            $this->writeDump($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
            if (! fflush($stream)) {
                throw new \RuntimeException('Unable to finish database dump.');
            }
        } catch (\Throwable $exception) {
            fclose($stream);
            @unlink($destination);

            throw $exception;
        }

        if (! fclose($stream)) {
            @unlink($destination);

            throw new \RuntimeException('Unable to finish database dump.');
        }
    }

    protected function pdoForDatabaseDump(): PDO
    {
        return app('db')->connection('mysql')->getPdo();
    }

    protected function isMysqlDumpPdo(PDO $pdo): bool
    {
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    protected function runMysqldumpProcess(array $command, array $environment, string $destination): void
    {
        $process = new Process($command, null, $environment);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database backup could not run on this server.');
        }
    }

    protected function listDumpTables(PDO $pdo): array
    {
        $statement = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    protected function createDumpTableStatement(PDO $pdo, string $table): string
    {
        $statement = $pdo->query('SHOW CREATE TABLE '.$this->quoteDumpIdentifier($table));
        $row = $statement->fetch(PDO::FETCH_NUM);
        if (! is_array($row) || ! isset($row[1]) || ! is_string($row[1])) {
            throw new \RuntimeException('Unable to read table definition.');
        }

        return rtrim($row[1], ';');
    }

    protected function listDumpColumns(PDO $pdo, string $table): array
    {
        $statement = $pdo->query('SHOW FULL COLUMNS FROM '.$this->quoteDumpIdentifier($table));
        $columns = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = [
                'name' => (string) $column['Field'],
                'type' => (string) $column['Type'],
                'extra' => (string) ($column['Extra'] ?? ''),
            ];
        }

        return $columns;
    }

    protected function fetchDumpBatch(PDO $pdo, string $table, int $offset, int $limit): array
    {
        $statement = $pdo->query(
            'SELECT * FROM '.$this->quoteDumpIdentifier($table).' LIMIT '.(int) $limit.' OFFSET '.(int) $offset
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function quoteDumpIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    protected function dumpSqlValue(PDO $pdo, mixed $value, string $type): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (preg_match('/^(?:tinyint|smallint|mediumint|int|integer|bigint|decimal|numeric|float|double|real|year)/i', $type)
            && is_scalar($value)
            && preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/', (string) $value)) {
            return (string) $value;
        }

        if (preg_match('/(?:tinyblob|mediumblob|longblob|blob|varbinary|binary)(?:\(|$)/i', $type)) {
            $binary = (string) $value;

            return $binary === '' ? "X''" : '0x'.bin2hex($binary);
        }

        $quoted = $pdo->quote((string) $value, PDO::PARAM_STR);
        if ($quoted === false) {
            throw new \RuntimeException('Unable to encode a database value.');
        }

        return $quoted;
    }

    protected function writeDump($stream, string $contents): void
    {
        $length = strlen($contents);
        $written = 0;
        while ($written < $length) {
            $bytes = fwrite($stream, substr($contents, $written));
            if ($bytes === false || $bytes === 0) {
                throw new \RuntimeException('Unable to write database dump.');
            }
            $written += $bytes;
        }
    }

    protected function canImportDatabase(): bool
    {
        if ($this->processAvailable()) {
            return true;
        }

        if (! extension_loaded('pdo_mysql')) {
            return false;
        }

        try {
            return $this->isMysqlImportPdo($this->pdoForDatabaseImport());
        } catch (\Throwable $exception) {
            return false;
        }
    }

    protected function importDatabase(string $sqlFile): void
    {
        if (! $this->processAvailable()) {
            if (! $this->canImportDatabase()) {
                throw new \RuntimeException('Restore is not available on this server');
            }

            $this->importDatabaseWithPdo($sqlFile);

            return;
        }

        $config = config('database.connections.mysql');
        $binary = config('backup.mysql_path');
        $environment = $this->buildWindowsProcessEnvironment();
        $environment['MYSQL_PWD'] = (string) ($config['password'] ?? '');

        $command = [
            $binary,
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? ''),
            ($config['database'] ?? ''),
        ];

        try {
            $this->runMysqlImportProcess($command, $environment, $sqlFile);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Database restore could not run on this server.');
        }
    }

    protected function pdoForDatabaseImport(): PDO
    {
        return app('db')->connection('mysql')->getPdo();
    }

    protected function isMysqlImportPdo(PDO $pdo): bool
    {
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    protected function importDatabaseWithPdo(string $sqlFile): void
    {
        $pdo = $this->pdoForDatabaseImport();
        if (! $this->isMysqlImportPdo($pdo)) {
            throw new \RuntimeException('Restore is not available on this server');
        }

        $statementCount = $this->validatePdoImportFile($sqlFile);
        if ($statementCount === 0) {
            throw new \RuntimeException('The SQL backup contains no importable statements.');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        $statementNumber = 0;
        $failure = null;

        try {
            $this->setImportForeignKeyChecks($pdo, false);

            foreach ($this->readPdoImportStatements($sqlFile) as $statement) {
                $statement = $this->supportedPdoImportStatement($statement);
                if ($statement === null) {
                    continue;
                }

                $statementNumber++;
                try {
                    $this->executePdoImportStatement($pdo, $statement);
                } catch (\Throwable $exception) {
                    throw new \RuntimeException(
                        'Database restore failed at statement '.$statementNumber.' ('.$this->safeStatementPreview($statement).'). '
                        .'Changes may have occurred; recovery from the pre-restore backup may be necessary.'
                    );
                }
            }
        } catch (\Throwable $exception) {
            $failure = $exception;
        } finally {
            try {
                $this->setImportForeignKeyChecks($pdo, true);
            } catch (\Throwable $exception) {
                if ($failure === null) {
                    $failure = new \RuntimeException('Database restore could not restore foreign-key checks.');
                }
            }
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    protected function validatePdoImportFile(string $sqlFile): int
    {
        $statementCount = 0;
        foreach ($this->readPdoImportStatements($sqlFile) as $statement) {
            $statement = $this->supportedPdoImportStatement($statement);
            if ($statement !== null) {
                $statementCount++;
            }
        }

        return $statementCount;
    }

    protected function readPdoImportStatements(string $sqlFile): \Generator
    {
        $stream = @fopen($sqlFile, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('The SQL backup could not be read.');
        }

        $buffer = '';
        $bufferIndex = 0;
        $pending = [];
        $readCharacter = function () use ($stream, &$buffer, &$bufferIndex, &$pending): ?string {
            if ($pending !== []) {
                return array_shift($pending);
            }

            if ($bufferIndex >= strlen($buffer)) {
                $buffer = fread($stream, 8192);
                $bufferIndex = 0;
                if ($buffer === false || $buffer === '') {
                    return null;
                }
            }

            return $buffer[$bufferIndex++];
        };
        $pushCharacter = function (string $character) use (&$pending): void {
            array_unshift($pending, $character);
        };

        $statement = '';
        $quote = null;
        $escaped = false;
        $blockComment = null;
        $lineComment = false;
        $parseError = null;

        try {
            while (($character = $readCharacter()) !== null) {
                if ($lineComment) {
                    if ($character === "\n" || $character === "\r") {
                        $lineComment = false;
                        $statement .= $character;
                    }
                    continue;
                }

                if ($blockComment !== null) {
                    if ($blockComment === 'ordinary') {
                        if ($character === '*') {
                            $next = $readCharacter();
                            if ($next === '/') {
                                $statement .= ' ';
                                $blockComment = null;
                            } elseif ($next !== null) {
                                $pushCharacter($next);
                            }
                        }
                        continue;
                    }

                    $statement .= $character;
                    if ($character === '*') {
                        $next = $readCharacter();
                        if ($next === '/') {
                            $statement .= '/';
                            $blockComment = null;
                        } elseif ($next !== null) {
                            $pushCharacter($next);
                        }
                    }
                    continue;
                }

                if ($quote !== null) {
                    $statement .= $character;
                    if ($escaped) {
                        $escaped = false;
                        continue;
                    }
                    if ($character === '\\') {
                        $escaped = true;
                        continue;
                    }
                    if ($character === $quote) {
                        $next = $readCharacter();
                        if ($next === $quote) {
                            $statement .= $next;
                        } else {
                            $quote = null;
                            if ($next !== null) {
                                $pushCharacter($next);
                            }
                        }
                    }
                    continue;
                }

                if ($character === "'" || $character === '"' || $character === '`') {
                    $quote = $character;
                    $statement .= $character;
                    continue;
                }

                if ($character === '#') {
                    $lineComment = true;
                    continue;
                }

                if ($character === '-') {
                    $next = $readCharacter();
                    if ($next === '-') {
                        $third = $readCharacter();
                        if ($third !== null && ord($third) <= 32) {
                            $lineComment = true;
                            continue;
                        }
                        $statement .= '--';
                        if ($third !== null) {
                            $pushCharacter($third);
                        }
                        continue;
                    }
                    $statement .= '-';
                    if ($next !== null) {
                        $pushCharacter($next);
                    }
                    continue;
                }

                if ($character === '/') {
                    $next = $readCharacter();
                    if ($next === '*') {
                        $third = $readCharacter();
                        if ($third === '!') {
                            $statement .= '/*!';
                            $blockComment = 'executable';
                        } elseif ($third === 'M' || $third === 'm') {
                            $marker = $readCharacter();
                            if ($marker === '!') {
                                $statement .= '/*'.$third.'!';
                                $blockComment = 'executable';
                            } else {
                                $statement .= ' ';
                                $blockComment = 'ordinary';
                                if ($marker !== null) {
                                    $pushCharacter($marker);
                                }
                            }
                        } else {
                            $blockComment = 'ordinary';
                            $statement .= ' ';
                            if ($third !== null) {
                                $pushCharacter($third);
                            }
                        }
                        continue;
                    }
                    $statement .= '/';
                    if ($next !== null) {
                        $pushCharacter($next);
                    }
                    continue;
                }

                if ($character === ';') {
                    $completed = trim($statement);
                    if ($completed !== '') {
                        yield $completed;
                    }
                    $statement = '';
                    continue;
                }

                $statement .= $character;
            }

            if ($quote !== null) {
                $parseError = 'The SQL backup contains an unterminated quoted value or identifier.';
            } elseif ($blockComment !== null) {
                $parseError = 'The SQL backup contains an unterminated block comment.';
            }

            if ($parseError !== null) {
                throw new \RuntimeException($parseError);
            }

            $completed = trim($statement);
            if ($completed !== '') {
                yield $completed;
            }
        } finally {
            fclose($stream);
        }
    }

    protected function supportedPdoImportStatement(string $statement): ?string
    {
        if (preg_match('/^\s*DELIMITER\b/i', $statement)) {
            throw new \RuntimeException('The SQL backup uses unsupported DELIMITER directives.');
        }

        $effectiveSql = $this->unwrappedConditionalSql($statement);
        if (preg_match('/^\s*CREATE\s+(?:(?:OR\s+REPLACE)\s+)?(?:(?:DEFINER\s*=\s*[^\s]+)\s+)?(?:TEMPORARY\s+)?(?:TRIGGER|PROCEDURE|FUNCTION|EVENT)\b/i', $effectiveSql)) {
            throw new \RuntimeException('The SQL backup contains unsupported triggers, routines, or events.');
        }

        if (preg_match('/^\s*CREATE\s+(?:DATABASE|SCHEMA)\b/i', $effectiveSql)
            || preg_match('/^\s*USE\b/i', $effectiveSql)
            || preg_match('/^\s*(?:LOCK\s+TABLES|UNLOCK\s+TABLES)\b/i', $effectiveSql)) {
            return null;
        }

        return $statement;
    }

    protected function unwrappedConditionalSql(string $statement): string
    {
        return preg_replace_callback(
            '~\/\*(?:!|[Mm]!)(?:\d{5,6})?\s*(.*?)\*\/\s*~s',
            static fn (array $match): string => ' '.$match[1].' ',
            $statement
        ) ?? $statement;
    }

    protected function executePdoImportStatement(PDO $pdo, string $statement): void
    {
        $pdo->exec($statement);
    }

    protected function runMysqlImportProcess(array $command, array $environment, string $sqlFile): void
    {
        $process = new Process($command, null, $environment, file_get_contents($sqlFile));
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database restore could not run on this server.');
        }
    }

    protected function setImportForeignKeyChecks(PDO $pdo, bool $enabled): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=' . ($enabled ? '1' : '0'));
    }

    protected function safeStatementPreview(string $statement): string
    {
        $preview = preg_replace("/'(?:\\\\.|''|[^'\\\\])*'|\"(?:\\\\.|\"\"|[^\"\\\\])*\"/s", "'[redacted]'", $statement) ?? '';
        $preview = preg_replace('/\b0x[0-9a-f]+\b/i', '[binary]', $preview) ?? $preview;
        $preview = preg_replace('/(?<![A-Za-z0-9_])[+-]?\d+(?:\.\d+)?(?![A-Za-z0-9_])/', '[value]', $preview) ?? $preview;
        $preview = preg_replace('/\s+/', ' ', trim($preview)) ?? trim($preview);

        return substr($preview, 0, 160);
    }

    protected function buildWindowsProcessEnvironment(): array
    {
        $env = array_merge($_ENV, $_SERVER, (array) getenv());

        $systemRoot = getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\\Windows';
        $path = getenv('PATH') ?: getenv('Path') ?: $env['PATH'] ?? '';
        $comSpec = getenv('ComSpec') ?: getenv('COMSPEC') ?: $systemRoot.'\\System32\\cmd.exe';

        $env['SystemRoot'] = $systemRoot;
        $env['SYSTEMROOT'] = $systemRoot;
        $env['Path'] = $path;
        $env['PATH'] = $path;
        $env['ComSpec'] = $comSpec;
        $env['COMSPEC'] = $comSpec;

        return $env;
    }

    protected function clearDirectory(string $directory): void
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
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
    }

    protected function checkPublicStorageWritable(string $directory): void
    {
        $this->checkStorageWritable($directory, 'storage/app/public');
    }

    protected function checkStorageWritable(string $directory, string $label): void
    {
        $probePath = $directory.DIRECTORY_SEPARATOR.'.restore-permission-check-'.bin2hex(random_bytes(8));
        $handle = @fopen($probePath, 'xb');

        try {
            if ($handle === false || fwrite($handle, 'restore permission check') === false) {
                throw new \RuntimeException('Unable to create or write the restore permission probe.');
            }
            if (! fclose($handle)) {
                $handle = false;
                throw new \RuntimeException('Unable to close the restore permission probe.');
            }
            $handle = false;
            if (! @unlink($probePath)) {
                throw new \RuntimeException('Unable to delete the restore permission probe.');
            }
        } catch (\Throwable $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($probePath);

            throw new \RuntimeException($label.' is not writable — check folder permissions', 0, $exception);
        }
    }

    protected function copyDirectoryForRestore(string $source, string $destination): void
    {
        if (is_link($source) || is_link($destination)) {
            throw new \RuntimeException('Refusing to copy restore files through a symlink or junction: '.(is_link($source) ? $source : $destination));
        }

        try {
            if (File::copyDirectory($source, $destination)) {
                return;
            }
            $problemFile = $this->findCopyProblemFile($source, $destination);
            $detail = $problemFile
                ? 'Unable to copy file ['.$problemFile.']; it may be locked or inaccessible.'
                : 'Unable to copy directory ['.$source.'] to ['.$destination.'].';
            throw new \RuntimeException($detail);
        } catch (\Throwable $exception) {
            if ($exception instanceof \RuntimeException && str_contains($exception->getMessage(), 'Unable to copy')) {
                throw $exception;
            }

            $problemFile = $this->findCopyProblemFile($source, $destination);
            $detail = $problemFile
                ? 'Unable to copy file ['.$problemFile.']; it may be locked or inaccessible.'
                : 'Unable to copy directory ['.$source.'] to ['.$destination.'].';
            throw new \RuntimeException($detail.' Filesystem detail: '.$exception->getMessage(), 0, $exception);
        }
    }

    protected function deleteDirectoryForRestore(string $directory): void
    {
        if (is_link($directory)) {
            throw new \RuntimeException('Refusing to delete a symlink or junction during restore: '.$directory);
        }
        if (! is_dir($directory)) {
            return;
        }

        try {
            if (File::deleteDirectory($directory)) {
                return;
            }
        } catch (\Throwable $exception) {
            $problemFile = $this->findRemainingFile($directory);
            $detail = $problemFile
                ? 'Unable to delete file ['.$problemFile.']; it may be locked or inaccessible.'
                : 'Unable to delete directory ['.$directory.'].';
            throw new \RuntimeException($detail.' Filesystem detail: '.$exception->getMessage(), 0, $exception);
        }

        $problemFile = $this->findRemainingFile($directory);
        $detail = $problemFile
            ? 'Unable to delete file ['.$problemFile.']; it may be locked or inaccessible.'
            : 'Unable to delete directory ['.$directory.'].';
        throw new \RuntimeException($detail);
    }

    protected function findCopyProblemFile(string $source, string $destination): ?string
    {
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $sourcePath = $file->getPathname();
                $relativePath = substr($sourcePath, strlen($source) + 1);
                $destinationPath = $destination.DIRECTORY_SEPARATOR.$relativePath;
                if (! is_file($destinationPath)
                    || filesize($sourcePath) !== filesize($destinationPath)
                    || hash_file('sha256', $sourcePath) !== hash_file('sha256', $destinationPath)) {
                    return $sourcePath;
                }
            }
        } catch (\Throwable) {
            return $source;
        }

        return null;
    }

    protected function findRemainingFile(string $directory): ?string
    {
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    return $file->getPathname();
                }
            }
        } catch (\Throwable) {
            return $directory;
        }

        return $directory;
    }

    protected function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $this->clearDirectory($directory);
        @rmdir($directory);
    }

    protected function backupDirectoryPath(): string
    {
        return storage_path('app/'.$this->backupDirectory);
    }

    protected function publicStoragePath(): string
    {
        return storage_path('app/public');
    }

    protected function privateStoragePath(): string
    {
        return storage_path('app/private');
    }
}
