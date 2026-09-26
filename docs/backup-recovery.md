# Backup and Recovery

## Create and download a backup

1. Sign in as an administrator and open **Backup & Restore**.
2. Select **Create Manual Backup**. The archive is saved under `storage/app/backups`.
3. Download the ZIP from **Available Backups** and store a copy in a separate, access-controlled location.

Each archive contains `database.sql` and the files under `storage/app/public`. Database backup and restore require the MySQL client tools configured through `BACKUP_MYSQLDUMP_PATH` and `BACKUP_MYSQL_PATH` in `config/backup.php`, and credentials with access to the database configured for Laravel.

## Restore an archive

1. Keep a separate copy of the archive and confirm it is the intended backup.
2. On **Backup & Restore**, find the archive and enter `RESTORE` in its confirmation field.
3. Submit **Restore** and confirm the browser warning. The action replaces the configured MySQL database and all files in `storage/app/public` with the archive contents.
4. Do not interrupt the request. For large archives, perform recovery during a maintenance window.
5. Verify organization records, recent submissions, and representative uploaded documents before reopening normal access.

Only restore trusted archives produced for this application. The service rejects archives without `database.sql` and rejects archive entries with absolute or parent-directory paths before extracting them.

## Recovery limitations

Restoring does not alter application code, `.env`, or files outside `storage/app/public`. Keep those items backed up separately. The administrator action currently restores the database and public files directly; it does not provide a transaction or automatic rollback if the database import fails partway through.