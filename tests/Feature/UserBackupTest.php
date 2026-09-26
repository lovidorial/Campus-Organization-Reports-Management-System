<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityReportPhoto;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserBackup;
use App\Services\StorageUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class UserBackupTest extends TestCase
{
    use RefreshDatabase;

    private array $testFiles = [];
    private ?string $testArchivePath = null;

    public function test_user_export_contains_only_owned_records_and_document_files(): void
    {
        $organization = Organization::create([
            'name' => 'Backup Test Organization',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
            'storage_limit_mb' => 500,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
        ]);
        $otherUser = User::factory()->create(['role' => 'user']);

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'status' => 'approved',
        ]);
        $plannedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Owned Planned Activity',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $plannedActivity->id,
            'title' => 'Owned Request',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'communication_letter' => $this->createPublicFile('communication.pdf', 'letter'),
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $report = ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => $this->createPublicFile('narrative.pdf', 'report'),
            'submitted_at' => now(),
        ]);
        ActivityReportPhoto::create([
            'activity_report_id' => $report->id,
            'path' => $this->createPublicFile('photo.jpg', 'photo'),
            'caption' => 'Owned photo',
        ]);

        $otherGpoa = Gpoa::create([
            'user_id' => $otherUser->id,
            'term' => '2nd Term',
            'school_year' => '2026-2027',
            'status' => 'pending',
        ]);

        $storageUsage = app(StorageUsageService::class);
        $usageBeforeExport = $storageUsage->organizationUsageBytes($organization->id);

        $response = $this->actingAs($user)->post(route('my-backup.export'));
        $response->assertRedirect(route('my-backup.index', absolute: false));
        $this->assertSame($usageBeforeExport, $storageUsage->organizationUsageBytes($organization->id));

        $backup = UserBackup::where('user_id', $user->id)->firstOrFail();
        $archivePath = storage_path('app/user-backups/' . $user->id . '/' . $backup->filename);
        $this->testArchivePath = $archivePath;
        $this->assertFileExists($archivePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archivePath));
        $gpoaJson = $zip->getFromName('data/gpoas.json');
        $requestJson = $zip->getFromName('data/activity-requests.json');

        $this->assertNotFalse($gpoaJson);
        $this->assertNotFalse($requestJson);
        $this->assertStringContainsString('Owned Planned Activity', $gpoaJson);
        $exportedGpoas = json_decode($gpoaJson, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([$gpoa->id], array_column($exportedGpoas, 'id'));
        $this->assertNotContains($otherGpoa->id, array_column($exportedGpoas, 'id'));
        $this->assertStringContainsString('Owned Request', $requestJson);
        $this->assertStringContainsString('Owned photo', $requestJson);
        $this->assertNotFalse($zip->locateName('documents/activity-requests/' . $request->id . '/communication.pdf'));
        $this->assertNotFalse($zip->locateName('documents/activity-requests/' . $request->id . '/narrative.pdf'));
        $this->assertNotFalse($zip->locateName('documents/activity-requests/' . $request->id . '/photos/' . $report->photos()->first()->id . '.jpg'));
        $zip->close();

        $download = $this->actingAs($user)->get(route('my-backup.download', $backup->filename));
        $download->assertOk();
        $download->assertDownload($backup->filename);

        $foreignDownload = $this->actingAs($otherUser)->get(route('my-backup.download', $backup->filename));
        $foreignDownload->assertForbidden();
    }

    protected function tearDown(): void
    {
        foreach ($this->testFiles as $path) {
            @unlink($path);
        }

        if ($this->testArchivePath) {
            @unlink($this->testArchivePath);
            @rmdir(dirname($this->testArchivePath));
            @rmdir(dirname(dirname($this->testArchivePath)));
        }

        parent::tearDown();
    }

    public function test_admin_cannot_use_regular_user_backup_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('my-backup.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('my-backup.export'))->assertForbidden();
    }

    private function createPublicFile(string $filename, string $contents): string
    {
        $path = 'user-backup-test/' . uniqid('', true) . '-' . $filename;
        $absolutePath = storage_path('app/public/' . $path);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }
        file_put_contents($absolutePath, $contents);
        $this->testFiles[] = $absolutePath;

        return $path;
    }
}