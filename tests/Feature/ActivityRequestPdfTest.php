<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
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

        $detailPage = $this->actingAs($owner)->get(route('activity-requests.show', $request));
        $detailPage->assertSee(route('activity-requests.pdf', $request));
    }

    public function test_pdf_is_not_available_before_approval_or_after_rejection(): void
    {
        $owner = User::factory()->create(['terms_accepted_at' => now()]);

        foreach ([ActivityRequest::STATUS_PENDING, ActivityRequest::STATUS_REJECTED] as $status) {
            $request = $this->createRequest($owner, $status);

            $this->actingAs($owner)
                ->get(route('activity-requests.show', $request))
                ->assertOk()
                ->assertDontSee(route('activity-requests.pdf', $request));

            $this->actingAs($owner)
                ->get(route('activity-requests.pdf', $request))
                ->assertForbidden();
        }
    }

    public function test_user_cannot_download_another_users_activity_request_pdf(): void
    {
        $owner = User::factory()->create(['terms_accepted_at' => now()]);
        $otherUser = User::factory()->create(['terms_accepted_at' => now()]);
        $request = $this->createRequest($owner, ActivityRequest::STATUS_APPROVED);

        $this->actingAs($otherUser)
            ->get(route('activity-requests.pdf', $request))
            ->assertForbidden();
    }

    public function test_admin_activity_monitoring_shows_pdf_action_only_for_approved_or_later_requests(): void
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
            ->assertSee('href="' . route('activity-requests.pdf', $approved) . '"', false)
            ->assertDontSee('href="' . route('activity-requests.pdf', $pending) . '"', false);
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