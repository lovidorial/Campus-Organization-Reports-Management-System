<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpoaMonitoringUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_gpoa_shows_monitoring_summary_and_submitted_status(): void
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
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('gpoa.index'));

        $response->assertOk();
        $response->assertSee('General Plan of Activities');
        $response->assertSee('Pending');
        $response->assertSee('Ongoing');
        $response->assertSee('Completed');
        $response->assertSee('>1</span>', false);
        $response->assertSee('33% complete');
        $response->assertSee('3 activities');
        $response->assertSee('Submitted');
        $response->assertDontSee('Step 1 of your document workflow');
        $response->assertDontSee('Workflow Progress');
        $response->assertDontSee('Approved');
        $response->assertDontSee('Rejected');

        $detailsResponse = $this->get(route('gpoa.show', $gpoa));
        $detailsResponse->assertOk();
        $detailsResponse->assertSee('Submitted');
        $detailsResponse->assertSee('Edit GPOA');
        $detailsResponse->assertSee('Pending');
        $detailsResponse->assertDontSee('Awaiting Approval');
        $detailsResponse->assertDontSee('Request a GPOA Activity Modification');
    }

    public function test_user_can_edit_legacy_approved_gpoa_without_losing_linked_monitoring_data(): void
    {
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
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('gpoa.edit', $gpoa))
            ->assertOk()
            ->assertSee('Edit GPOA')
            ->assertDontSee('Pending Review');

        $response = $this->put(route('gpoa.update', $gpoa), [
            'colleges' => 'CICS',
            'prepared_by' => 'Updated Officer',
            'planned_activities' => [[
                'id' => $activity->id,
                'title' => 'Updated Community Outreach',
                'date' => '2027-03-10',
                'venue' => 'Community Center',
                'category' => 'Outreach',
                'sdgs' => [4],
            ]],
            'verify' => '1',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('gpoa_activities', [
            'id' => $activity->id,
            'title' => 'Updated Community Outreach',
            'activity_request_id' => $activityRequest->id,
        ]);
        $this->assertDatabaseHas('activity_reports', [
            'activity_request_id' => $activityRequest->id,
            'narrative_report' => 'reports/community.pdf',
        ]);
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
