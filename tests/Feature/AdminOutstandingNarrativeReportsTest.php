<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\DocumentDeadline;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOutstandingNarrativeReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_table_classifies_reports_excludes_archived_and_dashboard_count_matches_outstanding_rows(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $organization = Organization::create([
            'name' => 'CICS Student Council',
            'type' => 'Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $owner = User::factory()->create([
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'college' => 'CICS',
            'terms_accepted_at' => now(),
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REPORT,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'deadline_date' => '2026-10-25',
            'grace_days' => 3,
        ]);
        $missingGpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '2nd Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REPORT,
            'term' => '2nd Term',
            'school_year' => '2026-2027',
            'deadline_date' => '2026-11-10',
            'grace_days' => 3,
        ]);

        $missing = $this->createLinkedActivity($owner, $missingGpoa, 'Missing Report Activity', '2026-10-20');
        $overdue = $this->createLinkedActivity($owner, $gpoa, 'Overdue Report Activity', '2026-10-20');
        $revision = $this->createLinkedActivity($owner, $gpoa, 'Needs Revision Activity', '2026-10-20');
        $submitted = $this->createLinkedActivity($owner, $gpoa, 'Submitted Report Activity', '2026-10-20');
        $archived = $this->createLinkedActivity($owner, $gpoa, 'Archived Activity', '2026-10-20', true);

        $this->createReport($revision['request'], 'reports/revision.pdf', 'needs_revision', '2026-10-31');
        $this->createReport($submitted['request'], 'reports/submitted.pdf', 'approved', '2026-10-31', true);
        $this->createReport($archived['request'], 'reports/archived.pdf', 'pending', '2026-10-31');

        $tableResponse = $this->actingAs($admin)
            ->get(route('admin.narrative-reports.outstanding'))
            ->assertOk()
            ->assertSee('Missing Report Activity')
            ->assertSee('Overdue Report Activity')
            ->assertSee('Needs Revision Activity')
            ->assertSee('Submitted Report Activity')
            ->assertDontSee('Archived Activity')
            ->assertSee('Missing')
            ->assertSee('Overdue')
            ->assertSee('Needs revision')
            ->assertSee('Submitted')
            ->assertSee('CICS Student Council')
            ->assertSee('Confirmed')
            ->assertSee('4 records shown, including submitted history');
        $statusByActivity = $tableResponse->viewData('reports')
            ->mapWithKeys(fn (array $row) => [$row['activity']->title => $row['report_status']]);
        $this->assertSame('Missing', $statusByActivity['Missing Report Activity']);
        $this->assertSame('Overdue', $statusByActivity['Overdue Report Activity']);
        $this->assertSame('Needs revision', $statusByActivity['Needs Revision Activity']);
        $this->assertSame('Submitted', $statusByActivity['Submitted Report Activity']);
        $this->assertDatabaseHas('activity_reports', [
            'activity_request_id' => $submitted['request']->id,
            'narrative_report' => 'reports/submitted.pdf',
        ]);

        $this->get(route('admin.narrative-reports.outstanding', ['search' => 'Overdue Report Activity']))
            ->assertOk()
            ->assertSee('Overdue Report Activity')
            ->assertDontSee('Missing Report Activity');
        $this->get(route('admin.narrative-reports.outstanding', [
            'activity_date_from' => '2026-10-20',
            'activity_date_to' => '2026-10-20',
        ]))->assertOk()->assertSee('Missing Report Activity');
        $this->get(route('admin.narrative-reports.outstanding', [
            'activity_date_from' => '2026-10-21',
            'activity_date_to' => '2026-10-22',
        ]))->assertOk()->assertDontSee('Missing Report Activity');

        $overdueResponse = $this->get(route('admin.narrative-reports.outstanding', ['status' => 'Overdue']))
            ->assertOk()
            ->assertSee('Overdue Report Activity');
        $this->assertCount(1, $overdueResponse->viewData('reports'));
        $this->assertSame('Overdue', $overdueResponse->viewData('reports')->first()['report_status']);

        $this->get(route('admin.narrative-reports.outstanding', [
            'organization' => $owner->id,
            'college' => 'CICS',
            'school_year' => '2026-2027',
            'term' => '1st Term',
        ]))->assertOk()
            ->assertSee('Submitted Report Activity');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Missing Reports')
            ->assertSee('>3</p>', false)
            ->assertSee(route('admin.narrative-reports.outstanding'), false);
    }

    public function test_non_admin_cannot_access_centralized_narrative_report_page(): void
    {
        $user = User::factory()->create(['role' => 'user', 'terms_accepted_at' => now()]);

        $this->actingAs($user)
            ->get(route('admin.narrative-reports.outstanding'))
            ->assertForbidden();
    }

    private function createLinkedActivity(User $owner, Gpoa $gpoa, string $title, string $date, bool $archived = false): array
    {
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => $title,
            'date' => $date,
            'end_date' => $date,
            'venue' => 'Main Hall',
            'category' => 'Education',
            'archived_at' => $archived ? now() : null,
        ]);
        $request = ActivityRequest::create([
            'user_id' => $owner->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $title,
            'date' => $date,
            'end_date' => $date,
            'venue' => 'Main Hall',
            'category' => 'Education',
            'communication_letter' => 'letters/' . str($title)->slug() . '.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $activity->update(['activity_request_id' => $request->id]);

        return compact('activity', 'request');
    }

    private function createReport(ActivityRequest $request, string $path, string $status, string $submittedAt, bool $headConfirmed = false): ActivityReport
    {
        return ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => $path,
            'narrative_source' => 'uploaded',
            'status' => $status,
            'submitted_at' => $submittedAt,
            'signed_by_organization_head' => $headConfirmed,
        ]);
    }
}