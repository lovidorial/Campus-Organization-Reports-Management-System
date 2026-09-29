<?php

namespace App\Services;

use App\Models\BackupArchive;
use App\Models\BackupSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    protected string $backupDirectory = 'backups';

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

        $process = new Process($command, null, $environment);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database backup failed: '.$process->getErrorOutput().' '.$process->getOutput());
        }
    }

    protected function importDatabase(string $sqlFile): void
    {
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

        $process = new Process($command, null, $environment, file_get_contents($sqlFile));
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database restore failed: '.$process->getErrorOutput().' '.$process->getOutput());
        }
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
