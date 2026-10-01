<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityDocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_communication_letter_requires_signed_confirmation_and_is_stored_privately(): void
    {
        Storage::fake('private');
        [$user, $request] = $this->createActivityRequest();

        $response = $this->actingAs($user)
            ->from(route('activity-requests.show', $request))
            ->post(route('activity-requests.communication-letter.store', $request), [
                'communication_letter' => UploadedFile::fake()->create('letter.pdf', 20, 'application/pdf'),
            ]);

        $response->assertRedirect(route('activity-requests.show', $request));
        $response->assertSessionHasErrors('signed_confirmation');
        $this->assertNull($request->fresh()->communication_letter);

        $this->from(route('activity-requests.show', $request))
            ->post(route('activity-requests.communication-letter.store', $request), [
                'communication_letter' => UploadedFile::fake()->create('letter.pdf', 20, 'application/pdf'),
                'signed_confirmation' => '1',
            ])
            ->assertRedirect(route('activity-requests.show', $request));

        $request->refresh();
        $this->assertNotNull($request->communication_letter_signed_at);
        $this->assertStringStartsWith('activity-documents/communication-letters/', $request->communication_letter);
        Storage::disk('private')->assertExists($request->communication_letter);

        $this->get(route('activity-requests.documents.show', [$request, 'communication-letter']))
            ->assertOk();
    }

    public function test_narrative_report_can_be_uploaded_for_an_unapproved_activity_and_completes_monitoring(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        [$user, $request, $activity] = $this->createActivityRequest('letters/activity.pdf');

        $this->actingAs($user)->get(route('activity-reports.create', $request))->assertOk();

        $response = $this->post(route('activity-reports.store', $request), $this->reportConfirmation([
            'narrative_source' => 'uploaded',
            'narrative_report' => UploadedFile::fake()->create('narrative.pdf', 40, 'application/pdf'),
            'photos' => [UploadedFile::fake()->image('activity.jpg')],
        ]));

        $response->assertRedirect(route('activity-requests.show', $request));
        $report = ActivityReport::where('activity_request_id', $request->id)->firstOrFail();
        $this->assertSame('uploaded', $report->narrative_source);
        $this->assertNull($report->narrative_content);
        $this->assertStringStartsWith('activity-documents/narrative-reports/', $report->narrative_report);
        Storage::disk('private')->assertExists($report->narrative_report);
        $this->assertSame('Ongoing', $activity->fresh()->monitoringStatus()['status']);

        $this->get(route('activity-requests.documents.show', [$request, 'narrative-report']))
            ->assertOk();
    }

    public function test_narrative_report_can_be_created_in_system_and_served_privately(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        [$user, $request, $activity] = $this->createActivityRequest('letters/activity.pdf');

        $response = $this->actingAs($user)->post(route('activity-reports.store', $request), $this->reportConfirmation([
            'narrative_source' => 'generated',
            'narrative_content' => 'The activity brought student volunteers together.',
            'photos' => [UploadedFile::fake()->image('activity.jpg')],
        ]));

        $response->assertRedirect(route('activity-requests.show', $request));
        $report = ActivityReport::where('activity_request_id', $request->id)->firstOrFail();
        $this->assertSame('generated', $report->narrative_source);
        $this->assertSame('The activity brought student volunteers together.', $report->narrative_content['body']);
        Storage::disk('private')->assertExists($report->narrative_report);
        $this->assertSame('Ongoing', $activity->fresh()->monitoringStatus()['status']);

        $this->get(route('activity-requests.documents.show', [$request, 'narrative-report']))
            ->assertOk();
    }

    public function test_private_documents_are_not_served_to_another_user(): void
    {
        Storage::fake('private');
        [$owner, $request] = $this->createActivityRequest('activity-documents/letters/private.pdf');
        Storage::disk('private')->put($request->communication_letter, 'private document');
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('activity-requests.documents.show', [$request, 'communication-letter']))
            ->assertForbidden();
    }

    private function createActivityRequest(?string $communicationLetter = null): array
    {
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'pending',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Student Leadership Seminar',
            'date' => '2025-03-10',
            'venue' => 'Main Hall',
            'category' => 'Seminar',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => $activity->category,
            'communication_letter' => $communicationLetter,
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $activity->update(['activity_request_id' => $request->id]);

        return [$user, $request, $activity];
    }

    private function reportConfirmation(array $values): array
    {
        return array_merge([
            'signed_by_secretary' => '1',
            'signed_by_governor' => '1',
            'signed_by_advisor' => '1',
            'signed_by_dean_president' => '1',
        ], $values);
    }
}
