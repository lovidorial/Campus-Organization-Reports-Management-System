<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityReportPhoto;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SensitiveDocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_public_sensitive_files_without_changing_files_or_records_then_move_migrates_only_documents(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $fixture = $this->createDocuments();
        $paths = $fixture['paths'];
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'legacy '.$path);
        }
        Storage::disk('public')->put('organization-logos/keep.png', 'brand image');

        $this->artisan('documents:move-sensitive-to-private')
            ->expectsOutputToContain('Dry run only. No files were changed.')
            ->assertExitCode(0);

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
            Storage::disk('private')->assertMissing($path);
        }
        Storage::disk('public')->assertExists('organization-logos/keep.png');
        $this->assertSame($fixture['request']->communication_letter, $fixture['request']->fresh()->communication_letter);
        $this->assertSame($fixture['gpoa']->document_path, $fixture['gpoa']->fresh()->document_path);

        $this->artisan('documents:move-sensitive-to-private', ['--move' => true])
            ->expectsOutputToContain('All eligible sensitive files were moved.')
            ->assertExitCode(0);

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
            Storage::disk('private')->assertExists($path);
        }
        Storage::disk('public')->assertExists('organization-logos/keep.png');
        $this->assertSame($fixture['request']->communication_letter, $fixture['request']->fresh()->communication_letter);
        $this->assertSame($fixture['report']->narrative_report, $fixture['report']->fresh()->narrative_report);
        $this->assertSame($fixture['gpoa']->document_path, $fixture['gpoa']->fresh()->document_path);
    }

    public function test_other_organization_gets_forbidden_for_every_document_route(): void
    {
        Storage::fake('private');
        $fixture = $this->createDocuments();
        $otherOrganization = Organization::create([
            'name' => 'Other Organization',
            'type' => 'Student Organization',
            'college' => 'CET',
            'is_active' => true,
        ]);
        $other = User::factory()->create([
            'organization_id' => $otherOrganization->id,
            'org_name' => $otherOrganization->name,
            'terms_accepted_at' => now(),
        ]);
        $this->putPrivateDocuments($fixture);

        foreach ($this->documentUrls($fixture) as $url) {
            $this->actingAs($other)->get($url)->assertForbidden();
        }
    }

    public function test_guests_are_redirected_for_every_document_route(): void
    {
        Storage::fake('private');
        $fixture = $this->createDocuments();
        $this->putPrivateDocuments($fixture);

        foreach ($this->documentUrls($fixture) as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_admin_can_view_each_document_type_through_authorized_routes(): void
    {
        Storage::fake('private');
        $fixture = $this->createDocuments();
        $this->putPrivateDocuments($fixture);
        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);

        $this->actingAs($admin)
            ->get(route('activity-requests.documents.show', [$fixture['request'], 'communication-letter']))
            ->assertOk();
        $this->get(route('activity-requests.documents.show', [$fixture['request'], 'narrative-report']))->assertOk();
        $this->get(route('activity-requests.documents.show', [$fixture['request'], 'attendance-sheet']))->assertOk();
        $this->get(route('activity-requests.report-photos.show', [$fixture['request'], $fixture['photo']]))->assertOk();
        $this->get(route('gpoa.document', $fixture['gpoa']))->assertOk();
        $this->get(route('admin.file.view', [$fixture['request']->id, 'communication']))->assertOk();
        $this->get(route('admin.file.download', [$fixture['request']->id, 'narrative']))->assertOk();
        $this->get(route('admin.reports.evidence', [$fixture['report'], 'attendance']))->assertOk();
        $this->get(route('admin.reports.evidence', [$fixture['report'], 'photo-' . $fixture['photo']->id]))->assertOk();
        $this->get(route('admin.gpoa.document', $fixture['gpoa']))->assertOk();
    }

    private function createDocuments(): array
    {
        $organization = Organization::create([
            'name' => 'Owner Organization',
            'type' => 'Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $owner = User::factory()->create([
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'terms_accepted_at' => now(),
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'document_path' => 'uploads/gpoa/owner.pdf',
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Storage Privacy Activity',
            'date' => '2026-10-15',
            'venue' => 'Main Hall',
            'category' => 'Academic',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $owner->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => $activity->category,
            'communication_letter' => 'activity-documents/communication-letters/letter.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $report = ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'activity-documents/narrative-reports/report.pdf',
            'attendance_sheet_path' => 'activity-documents/attendance-sheets/attendance.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
            'status' => 'pending',
        ]);
        $photo = ActivityReportPhoto::create([
            'activity_report_id' => $report->id,
            'path' => 'activity-documents/activity-photos/photo.jpg',
            'sort_order' => 0,
        ]);

        return compact('organization', 'owner', 'gpoa', 'activity', 'request', 'report', 'photo') + [
            'paths' => [
                $request->communication_letter,
                $report->narrative_report,
                $report->attendance_sheet_path,
                $gpoa->document_path,
                $photo->path,
            ],
        ];
    }

    private function putPrivateDocuments(array $fixture): void
    {
        foreach ($fixture['paths'] as $path) {
            Storage::disk('private')->put($path, 'private '.$path);
        }
    }

    private function documentUrls(array $fixture): array
    {
        return [
            route('activity-requests.documents.show', [$fixture['request'], 'communication-letter']),
            route('activity-requests.documents.show', [$fixture['request'], 'narrative-report']),
            route('activity-requests.documents.show', [$fixture['request'], 'attendance-sheet']),
            route('activity-requests.report-photos.show', [$fixture['request'], $fixture['photo']]),
            route('gpoa.document', $fixture['gpoa']),
            route('admin.file.view', [$fixture['request']->id, 'communication']),
            route('admin.file.download', [$fixture['request']->id, 'narrative']),
            route('admin.reports.evidence', [$fixture['report'], 'attendance']),
            route('admin.reports.evidence', [$fixture['report'], 'photo-' . $fixture['photo']->id]),
            route('admin.gpoa.document', $fixture['gpoa']),
        ];
    }
}
