<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class ActivityRequestPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_pdf_for_approved_request_with_program_flow(): void
    {
        $owner = User::factory()->create([
            'org_name' => 'CICS Student Council',
            'terms_accepted_at' => now(),
        ]);
        $request = $this->createRequest($owner, ActivityRequest::STATUS_APPROVED);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
        ]);
        $request->update(['gpoa_id' => $gpoa->id, 'end_date' => '2026-10-16']);
        $request->programFlows()->create([
            'time' => '9:00 AM',
            'flow' => 'Opening Remarks',
            'person_in_charge' => 'Event Host',
            'sort_order' => 0,
        ]);

        $pdfResponse = $this->actingAs($owner)
            ->get(route('activity-requests.pdf', $request))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=activity-request-' . $request->id . '.pdf');

        $pdfText = (new Parser())->parseContent($pdfResponse->getContent())->getText();
        $this->assertStringContainsString('CICS Student Council', $pdfText);
        $this->assertStringContainsString('Leadership Symposium', $pdfText);
        $this->assertStringContainsString('Build leadership skills.', $pdfText);
        $this->assertStringContainsString('Opening Remarks', $pdfText);
        $this->assertStringContainsString('CICS', $pdfText);
        $this->assertStringContainsString('October 15, 2026', $pdfText);
        $this->assertStringContainsString('October 16, 2026', $pdfText);
        $this->assertStringContainsString('Event Coordinator', $pdfText);

        $detailPage = $this->actingAs($owner)->get(route('activity-requests.show', $request));
        $detailPage->assertSee(route('activity-requests.pdf', $request));
    }

    public function test_concept_paper_html_shows_activity_data_and_skips_empty_rows(): void
    {
        $owner = User::factory()->create(['org_name' => 'CICS Student Council']);
        $request = $this->createRequest($owner, ActivityRequest::STATUS_APPROVED);
        $request->update([
            'objectives' => null,
            'sdgs' => null,
            'description' => null,
            'person_in_charge' => null,
            'target_participants' => null,
            'participants_count' => null,
            'estimated_budget' => null,
            'source_of_funds' => null,
        ]);
        $request->load(['user.organization', 'gpoa', 'programFlows']);

        $html = view('users.activity-request-pdf', [
            'activityRequest' => $request,
            'organizationName' => 'CICS Student Council',
            'organizationCollege' => 'CICS',
            'templateImages' => [
                'csuLogo' => 'data:image/jpeg;base64,ZmFrZQ==',
                'campusBuilding' => 'data:image/jpeg;base64,ZmFrZQ==',
                'footerLogos' => 'data:image/jpeg;base64,ZmFrZQ==',
            ],
        ])->render();

        $this->assertStringContainsString('CONCEPT PAPER', $html);
        $this->assertStringContainsString('CAGAYAN STATE UNIVERSITY', $html);
        $this->assertStringContainsString('Leadership Symposium', $html);
        $this->assertStringContainsString('Main Hall', $html);
        $this->assertStringContainsString('Face to Face', $html);
        $this->assertStringNotContainsString('Request ID', $html);
        $this->assertStringNotContainsString('BUDGETARY REQUIREMENTS', $html);
        $this->assertStringNotContainsString('<th>Objectives</th>', $html);
        $this->assertStringNotContainsString('<th>SDG Component</th>', $html);
        $this->assertStringNotContainsString('<th>Brief Description</th>', $html);
        $this->assertStringNotContainsString('<th>Program of activities</th>', $html);
    }

    public function test_activity_details_pdf_is_available_without_an_approval_state(): void
    {
        $owner = User::factory()->create(['terms_accepted_at' => now()]);

        foreach ([ActivityRequest::STATUS_PENDING, ActivityRequest::STATUS_REJECTED] as $status) {
            $request = $this->createRequest($owner, $status);

            $this->actingAs($owner)
                ->get(route('activity-requests.show', $request))
                ->assertOk()
                ->assertSee(route('activity-requests.pdf', $request));

            $this->actingAs($owner)
                ->get(route('activity-requests.pdf', $request))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_user_cannot_download_another_users_activity_request_pdf(): void
    {
        $ownerOrganization = Organization::create(['name' => 'Owner Org', 'type' => 'Student Organization', 'college' => 'CICS', 'is_active' => true]);
        $otherOrganization = Organization::create(['name' => 'Other Org', 'type' => 'Student Organization', 'college' => 'CICS', 'is_active' => true]);
        $owner = User::factory()->create(['organization_id' => $ownerOrganization->id, 'terms_accepted_at' => now()]);
        $otherUser = User::factory()->create(['organization_id' => $otherOrganization->id, 'terms_accepted_at' => now()]);
        $request = $this->createRequest($owner, ActivityRequest::STATUS_APPROVED);

        $this->actingAs($otherUser)
            ->get(route('activity-requests.pdf', $request))
            ->assertForbidden();
    }

    public function test_admin_can_download_an_activity_request_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $owner = User::factory()->create(['terms_accepted_at' => now()]);
        $request = $this->createRequest($owner, ActivityRequest::STATUS_APPROVED);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => now()->year . '-' . (now()->year + 1),
            'college' => 'CICS',
        ]);
        $activity = $gpoa->activities()->create([
            'title' => $request->title,
            'date' => $request->date,
            'venue' => $request->venue,
            'category' => $request->category,
        ]);
        $request->update(['gpoa_id' => $gpoa->id, 'gpoa_activity_id' => $activity->id]);
        $activity->update(['activity_request_id' => $request->id]);

        $this->actingAs($admin)
            ->get(route('activity-requests.pdf', $request))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=activity-request-' . $request->id . '.pdf');

        $this->actingAs($admin)
            ->get(route('admin.activity-requests.show', $request))
            ->assertOk()
            ->assertSee(route('activity-requests.pdf', $request));
    }

    public function test_pdf_action_is_shown_on_admin_and_officer_activity_lists(): void
    {
        $owner = User::factory()->create(['terms_accepted_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => now()->year . '-' . (now()->year + 1),
            'college' => 'CICS',
        ]);
        $activity = $gpoa->activities()->create([
            'title' => 'Leadership Symposium',
            'date' => now()->addWeek()->toDateString(),
            'venue' => 'Main Hall',
            'category' => 'Academic',
        ]);
        $request = $this->createRequest($owner, ActivityRequest::STATUS_APPROVED);
        $request->update([
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'communication_letter' => 'letters/communication.pdf',
        ]);
        $activity->update(['activity_request_id' => $request->id]);
        $pdfUrl = route('activity-requests.pdf', $request);

        $this->actingAs($owner)
            ->get(route('activity-monitor.index'))
            ->assertOk()
            ->assertSee($pdfUrl);

        $this->actingAs($admin)
            ->get(route('admin.activities'))
            ->assertOk()
            ->assertSee($pdfUrl);
    }

    public function test_admin_activity_monitoring_is_scoped_to_planned_gpoa_activities(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'terms_accepted_at' => now(),
        ]);
        $owner = User::factory()->create(['terms_accepted_at' => now()]);
        $approved = $this->createRequest($owner, ActivityRequest::STATUS_CLOSED);
        $pending = $this->createRequest($owner, ActivityRequest::STATUS_PENDING);

        $monitoring = $this->actingAs($admin)->get(route('admin.activities'));

        $monitoring->assertOk()
            ->assertSee('No recent submissions match these filters.')
            ->assertDontSee('Approve</button>')
            ->assertDontSee('Review Report');
    }

    private function createRequest(User $owner, string $status): ActivityRequest
    {
        return ActivityRequest::create([
            'user_id' => $owner->id,
            'title' => 'Leadership Symposium',
            'category' => 'Academic',
            'date' => '2026-10-15',
            'venue' => 'Main Hall',
            'description' => 'A printable request record.',
            'objectives' => 'Build leadership skills.',
            'expected_outcome' => 'A successful event.',
            'plan_key_strategy' => 'Facilitated workshop.',
            'target_participants' => 'Student leaders',
            'person_in_charge' => 'Event Coordinator',
            'facilities_materials' => 'Projector',
            'estimated_budget' => 1200,
            'source_of_funds' => 'Student funds',
            'status' => $status,
        ]);
    }
}