<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminActivityMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_monitor_lists_gpoa_activities_with_derived_statuses_and_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);

        $notStarted = $this->plannedActivity($gpoa, 'No Documents', '2026-09-01');
        $ongoing = $this->plannedActivity($gpoa, 'Letter Only', '2026-10-10');
        $completed = $this->plannedActivity($gpoa, 'Both Documents', '2026-11-10');
        $otherUser = User::factory()->create(['org_name' => 'CET Society']);
        $otherGpoa = Gpoa::create([
            'user_id' => $otherUser->id,
            'term' => '2nd Term',
            'school_year' => '2027-2028',
            'college' => 'CET',
            'status' => 'pending',
        ]);
        $otherTermActivity = $this->plannedActivity($otherGpoa, 'Other Term Activity', '2027-01-10');
        $otherTermActivity->update(['category' => 'Community']);

        $letterRequest = $this->createRequest($user, $gpoa, $ongoing, 'letters/letter.pdf');
        $ongoing->update(['activity_request_id' => $letterRequest->id]);

        $completeRequest = $this->createRequest($user, $gpoa, $completed, 'letters/complete.pdf');
        $completed->update(['activity_request_id' => $completeRequest->id]);
        ActivityReport::create([
            'activity_request_id' => $completeRequest->id,
            'narrative_report' => 'reports/complete.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
        ]);

        $page = $this->actingAs($admin)->get(route('admin.activities'));
        $page->assertOk()
            ->assertSee('Organization GPOA Progress')
            ->assertSee('Pending')
            ->assertSee('Ongoing')
            ->assertSee('Completed')
            ->assertSee('Late')
            ->assertSee('No Documents')
            ->assertSee('Letter Only')
            ->assertSee('Both Documents')
            ->assertDontSee('Awaiting Resubmission')
            ->assertDontSee('Review Report')
            ->assertDontSee('Approve</button>');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['status' => 'Completed']))
            ->assertOk()
            ->assertSee('Both Documents')
            ->assertDontSee('No Documents')
            ->assertDontSee('Letter Only');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['status' => 'Late']))
            ->assertOk()
            ->assertSee('No Documents')
            ->assertDontSee('Both Documents');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['college' => 'CET', 'term' => '2nd Term', 'school_year' => '2027-2028']))
            ->assertOk()
            ->assertSee('Other Term Activity')
            ->assertDontSee('No Documents');

        $this->actingAs($admin)
            ->get(route('admin.activities', ['organization' => $user->id, 'category' => 'Academic']))
            ->assertOk()
            ->assertSee('Organization GPOA Progress')
            ->assertSee('Both Documents')
            ->assertDontSee('Other Term Activity')
            ->assertSee('33%');
    }

    public function test_admin_can_save_an_audited_remark_for_a_planned_activity_without_a_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['org_name' => 'CICS Student Council']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Remark Without Request', '2026-10-15');

        $this->actingAs($admin)->post(route('admin.monitoring.record', $activity->id), [
            'compliance_status' => 'partial',
            'compliance_notes' => 'Confirm the participant count during the next check.',
        ])->assertRedirect(route('admin.activities', absolute: false));

        $this->assertDatabaseHas('monitoring_results', [
            'activity_request_id' => null,
            'gpoa_activity_id' => $activity->id,
            'admin_id' => $admin->id,
            'compliance_status' => 'partial',
            'compliance_notes' => 'Confirm the participant count during the next check.',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'monitoring.remark_recorded',
            'causer_id' => $admin->id,
        ]);
        $this->assertSame('Pending', $activity->fresh()->monitoringStatus()['status']);
    }

    public function test_admin_can_download_private_activity_documents_from_monitoring(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Private Documents', '2026-10-15');
        $request = $this->createRequest($user, $gpoa, $activity, 'activity-documents/letter.pdf');
        Storage::disk('private')->put($request->communication_letter, 'letter pdf');
        $activity->update(['activity_request_id' => $request->id]);

        $this->actingAs($admin)
            ->get(route('admin.file.download', [$request->id, 'communication']))
            ->assertOk()
            ->assertDownload('Communication-Letter-' . $request->id . '.pdf');
    }

    public function test_admin_dashboard_uses_monitoring_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create([
            'name' => 'CICS Student Council',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'org_name' => $organization->name,
            'organization_id' => $organization->id,
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = $this->plannedActivity($gpoa, 'Monitoring Dashboard Activity', '2026-10-15');
        $request = $this->createRequest($user, $gpoa, $activity, 'letters/dashboard.pdf');
        $activity->update(['activity_request_id' => $request->id]);
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/dashboard.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Monitoring Dashboard')
            ->assertSee('Active Organizations')
            ->assertSee('Pending')
            ->assertSee('Monitoring Dashboard Activity')
            ->assertSee('Organizations by Completed Activities')
            ->assertSee('Activities by Category')
            ->assertSee('Recent Document Submissions')
            ->assertSee('Communication Letter')
            ->assertSee('Narrative Report')
            ->assertDontSee('Pending GPOAs')
            ->assertDontSee('Recent Submissions');
    }

    private function plannedActivity(Gpoa $gpoa, string $title, string $date): GpoaActivity
    {
        return GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => $title,
            'date' => $date,
            'venue' => 'Main Hall',
            'category' => 'Academic',
        ]);
    }

    private function createRequest(User $user, Gpoa $gpoa, GpoaActivity $activity, string $letterPath): ActivityRequest
    {
        return ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => $activity->category,
            'communication_letter' => $letterPath,
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
    }
}
