<?php

/**
 * Backup Configuration
 * Handles both Windows (XAMPP) and Linux (Railway) environments
 */
$isWindows = PHP_OS_FAMILY === 'Windows';

return [
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', $isWindows ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump'),
    'mysql_path' => env('BACKUP_MYSQL_PATH', $isWindows ? 'C:\\xampp\\mysql\\bin\\mysql.exe' : 'mysql'),
    'max_restore_upload_mb' => (int) env('BACKUP_MAX_RESTORE_UPLOAD_MB', 2048),
];
