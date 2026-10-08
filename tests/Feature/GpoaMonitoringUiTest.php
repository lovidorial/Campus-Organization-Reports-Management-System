<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GpoaMonitoringUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_gpoa_shows_monitoring_summary_and_approved_status(): void
    {
        $user = $this->createOrganizationUser();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        $notStarted = $gpoa->activities()->create([
            'title' => 'Planning Meeting',
            'date' => '2027-01-10',
            'venue' => 'Main Hall',
            'category' => 'Meeting',
        ]);
        $ongoing = $gpoa->activities()->create([
            'title' => 'Leadership Seminar',
            'date' => '2027-02-10',
            'venue' => 'Main Hall',
            'category' => 'Seminar',
        ]);
        $completed = $gpoa->activities()->create([
            'title' => 'Community Outreach',
            'date' => '2027-03-10',
            'venue' => 'Community Center',
            'category' => 'Outreach',
        ]);

        $letterRequest = $this->createActivityRequest($user, $gpoa, $ongoing, 'letters/ongoing.pdf');
        $ongoing->update(['activity_request_id' => $letterRequest->id]);

        $completedRequest = $this->createActivityRequest($user, $gpoa, $completed, 'letters/completed.pdf');
        $completed->update(['activity_request_id' => $completedRequest->id]);
        ActivityReport::create([
            'activity_request_id' => $completedRequest->id,
            'narrative_report' => 'reports/completed.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('gpoa.index'));

        $response->assertOk();
        $response->assertSee('General Plan of Action');
        $response->assertSee('Pending');
        $response->assertSee('Ongoing');
        $response->assertSee('Completed');
        $response->assertSee('>1</span>', false);
        $response->assertSee('33% complete');
        $response->assertSee('3 activities');
        $response->assertSee('Approved');
        $response->assertDontSee('Step 1 of your document workflow');
        $response->assertDontSee('Workflow Progress');
        $response->assertDontSee('Rejected');

        $detailsResponse = $this->get(route('gpoa.show', $gpoa));
        $detailsResponse->assertOk();
        $detailsResponse->assertSee('Approved');
        $detailsResponse->assertSee('Locked after submission');
        $detailsResponse->assertDontSee(route('gpoa.edit', $gpoa), false);
        $detailsResponse->assertSee('Pending');
        $detailsResponse->assertDontSee('Awaiting Approval');
        $detailsResponse->assertDontSee('Request a GPOA Activity Modification');
    }

    public function test_admin_gpoa_pages_show_approved_status_and_document_link(): void
    {
        $user = $this->createOrganizationUser();
        $admin = User::factory()->create(['role' => 'admin']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'document_path' => 'uploads/gpoa/approved.pdf',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.gpoa.index'))
            ->assertOk()
            ->assertSee('Approved')
            ->assertSee(route('admin.gpoa.document', $gpoa));

        $this->get(route('admin.gpoa.show', $gpoa))
            ->assertOk()
            ->assertSee('Approved')
            ->assertSee(route('admin.gpoa.document', $gpoa));
    }

    public function test_officer_cannot_edit_submitted_gpoa_and_sees_locked_notice(): void
    {
        Storage::fake('public');
        $user = $this->createOrganizationUser();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'prepared_by' => 'Original Officer',
            'status' => 'approved',
        ]);
        $activity = $gpoa->activities()->create([
            'title' => 'Community Outreach',
            'date' => '2027-03-10',
            'venue' => 'Community Center',
            'category' => 'Outreach',
            'sdgs' => [4],
        ]);
        $activityRequest = $this->createActivityRequest($user, $gpoa, $activity, 'letters/community.pdf');
        $activity->update(['activity_request_id' => $activityRequest->id]);
        ActivityReport::create([
            'activity_request_id' => $activityRequest->id,
            'narrative_report' => 'reports/community.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('gpoa.edit', $gpoa))
            ->assertForbidden();

        $this->actingAs($user)->put(route('gpoa.update', $gpoa), [
            'colleges' => 'CICS',
            'prepared_by' => 'Updated Officer',
            'document_path' => UploadedFile::fake()->create('approved-gpoa.pdf', 20, 'application/pdf'),
            'planned_activities' => [[
                'id' => $activity->id,
                'title' => 'Updated Community Outreach',
                'time_frame' => 'exact_date',
                'date' => '2027-03-10',
                'venue' => 'Community Center',
                'category' => 'Outreach',
                'sdgs' => [4],
            ]],
            'verify' => '1',
        ])->assertForbidden();

        $this->actingAs($user)
            ->get(route('gpoa.index'))
            ->assertOk()
            ->assertSee('Locked after submission')
            ->assertDontSee(route('gpoa.edit', $gpoa), false);

        $this->get(route('gpoa.show', $gpoa))
            ->assertOk()
            ->assertSee('Locked after submission')
            ->assertDontSee(route('gpoa.edit', $gpoa), false);

        $this->assertSame('Community Outreach', $activity->fresh()->title);
        $this->assertSame($activityRequest->id, $activity->fresh()->activity_request_id);
    }

    private function createOrganizationUser(): User
    {
        $organization = Organization::create([
            'name' => 'CICS SC',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);

        return User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'org_type' => $organization->type,
            'college' => $organization->college,
        ]);
    }

    private function createActivityRequest(User $user, Gpoa $gpoa, GpoaActivity $activity, string $letterPath): ActivityRequest
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
            'status' => 'pending',
        ]);
    }
}
