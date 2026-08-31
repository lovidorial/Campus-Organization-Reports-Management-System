<?php

namespace App\Services;

use App\Models\BackupSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    protected string $backupDirectory = 'backups';

    public function createBackup(): array
    {
        $backupPath = storage_path('app/' . $this->backupDirectory);
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $fileName = 'backup_' . $timestamp . '.zip';
        $fullPath = $backupPath . DIRECTORY_SEPARATOR . $fileName;

        $storagePublicPath = storage_path('app/public');

        $zip = new ZipArchive();
        $zipOpenResult = $zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($zipOpenResult !== true) {
            throw new \RuntimeException('Unable to create backup archive: code ' . $zipOpenResult);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storagePublicPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $relativePath = str_replace('\\', '/', ltrim(str_replace($storagePublicPath, '', $file->getPathname()), DIRECTORY_SEPARATOR));
            if ($relativePath === '') {
                continue;
            }

            if ($file->isDir()) {
                continue;
            }

            $zip->addFile($file->getPathname(), 'storage/public/' . ltrim($relativePath, '/'));
        }

        $dbDumpPath = $backupPath . DIRECTORY_SEPARATOR . 'database.sql';
        $this->dumpDatabase($dbDumpPath);
        $zip->addFile($dbDumpPath, 'database.sql');

        $zip->close();
        unlink($dbDumpPath);

        $size = file_exists($fullPath) ? filesize($fullPath) : 0;

        return [
            'path' => $fullPath,
            'filename' => $fileName,
            'size' => $size,
        ];
    }

    public function restoreBackup(string $backupFilePath): bool
    {
        $resolvedPath = realpath($backupFilePath);
        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($resolvedPath) !== true) {
            return false;
        }

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'orgtrack_restore_' . uniqid();
        mkdir($tempDir, 0777, true);

        $zip->extractTo($tempDir);
        $zip->close();

        $publicStorage = storage_path('app/public');
        if (! is_dir($publicStorage)) {
            mkdir($publicStorage, 0777, true);
        }

        $this->clearDirectory($publicStorage);

        $sourcePublic = $tempDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'public';
        if (is_dir($sourcePublic)) {
            $this->copyDirectory($sourcePublic, $publicStorage);
        }

        $sqlFile = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
        if (file_exists($sqlFile)) {
            $this->importDatabase($sqlFile);
        }

        $this->deleteDirectory($tempDir);

        return true;
    }

    public function pruneOldBackups(): void
    {
        if (! Schema::hasTable('backup_settings')) {
            return;
        }

        $settings = BackupSetting::query()->first();
        $retentionCount = $settings?->retention_count ?? 5;

        $files = $this->listBackupFiles();
        if (count($files) <= $retentionCount) {
            return;
        }

        usort($files, fn ($a, $b) => $a['created_at'] <=> $b['created_at']);

        $filesToDelete = array_slice($files, 0, count($files) - $retentionCount);
        foreach ($filesToDelete as $file) {
            @unlink($file['path']);
        }
    }

    public function listBackupFiles(): array
    {
        $dir = storage_path('app/' . $this->backupDirectory);
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.zip') as $file) {
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

    protected function dumpDatabase(string $destination): void
    {
        $config = config('database.connections.mysql');
        $binary = config('backup.mysqldump_path');

        $command = [
            $binary,
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? '3306'),
            '--user=' . ($config['username'] ?? ''),
            '--password=' . ($config['password'] ?? ''),
            '--databases',
            ($config['database'] ?? ''),
            '--result-file=' . $destination,
        ];

        $process = new Process($command, null, $this->buildWindowsProcessEnvironment());
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database backup failed: ' . $process->getErrorOutput() . ' ' . $process->getOutput());
        }
    }

    protected function importDatabase(string $sqlFile): void
    {
        $config = config('database.connections.mysql');
        $binary = config('backup.mysql_path');

        $command = [
            $binary,
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? '3306'),
            '--user=' . ($config['username'] ?? ''),
            '--password=' . ($config['password'] ?? ''),
            ($config['database'] ?? ''),
        ];

        $process = new Process($command, null, $this->buildWindowsProcessEnvironment(), file_get_contents($sqlFile));
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database restore failed: ' . $process->getErrorOutput() . ' ' . $process->getOutput());
        }
    }

    protected function buildWindowsProcessEnvironment(): array
    {
        $env = array_merge($_ENV, $_SERVER, (array) getenv());

        $systemRoot = getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\\Windows';
        $path = getenv('PATH') ?: getenv('Path') ?: $env['PATH'] ?? '';
        $comSpec = getenv('ComSpec') ?: getenv('COMSPEC') ?: $systemRoot . '\\System32\\cmd.exe';

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

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        foreach (scandir($source) ?: [] as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $destPath = $destination . DIRECTORY_SEPARATOR . $item;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }

    protected function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $this->clearDirectory($directory);
        @rmdir($directory);
    }
}
