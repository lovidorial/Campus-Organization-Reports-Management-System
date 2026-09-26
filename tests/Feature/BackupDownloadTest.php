<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class BackupDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_an_existing_backup_archive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'backup_download_test.zip';
        $contents = 'backup download response';
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $contents);

        try {
            $response = $this->actingAs($admin)->get(route('admin.backups.download', ['filename' => $filename]));
            $response->assertOk();
            $response->assertDownload($filename);
            $this->assertSame($contents, $response->streamedContent());
        } finally {
            @unlink($directory . DIRECTORY_SEPARATOR . $filename);
        }
    }

    public function test_restore_rejects_archive_without_a_database_dump_before_mutating_files(): void
    {
        $archivePath = tempnam(sys_get_temp_dir(), 'backup-test-');
        $zip = new ZipArchive();
        $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('storage/public/should-not-be-restored.txt', 'payload');
        $zip->close();

        try {
            (new BackupService())->restoreBackup($archivePath);
            $this->fail('Expected restore to reject an archive without database.sql.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('The archive does not contain the required database.sql dump.', $exception->getMessage());
        } finally {
            @unlink($archivePath);
        }
    }
}