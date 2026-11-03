<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityReportPhoto;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use App\Models\OrganizationWorkflow;
use App\Models\WorkflowSubmission;
use App\Services\DocumentValidationService;
use Barryvdh\DomPDF\Facade\Pdf;
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
                'communication_letter' => $this->pdf('Campus Organization<br>Activity: Student Leadership Seminar<br>Date: March 10, 2025<br>Signed by: President'),
            ]);

        $response->assertRedirect(route('activity-requests.show', $request));
        $response->assertSessionHasErrors('signed_confirmation');
        $this->assertNull($request->fresh()->communication_letter);

        $this->from(route('activity-requests.show', $request))
            ->post(route('activity-requests.communication-letter.store', $request), [
                'communication_letter' => $this->pdf('Campus Organization<br>Activity: Student Leadership Seminar<br>Date: March 10, 2025<br>Signed by: President'),
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

    public function test_invalid_communication_letter_is_not_stored(): void
    {
        Storage::fake('private');
        [$user, $request] = $this->createActivityRequest();
        $file = $this->pdf('Wrong Organization<br>Activity: Unrelated Event<br>Date: April 4, 2026<br>Signed by: Officer');

        $this->actingAs($user)
            ->from(route('activity-requests.show', $request))
            ->post(route('activity-requests.communication-letter.store', $request), [
                'communication_letter' => $file,
                'signed_confirmation' => '1',
            ])
            ->assertRedirect(route('activity-requests.show', $request))
            ->assertSessionHasErrors('communication_letter');

        $this->assertNull($request->fresh()->communication_letter);
        Storage::disk('private')->assertDirectoryEmpty('activity-documents/communication-letters');
        $this->assertDatabaseHas('workflow_events', [
            'event_type' => 'document_validated',
            'description' => 'Communication letter validation: invalid',
        ]);
    }

    public function test_needs_review_communication_letter_is_stored_and_logged(): void
    {
        Storage::fake('private');
        [$user, $request] = $this->createActivityRequest();
        $this->app->bind(DocumentValidationService::class, fn () => new class extends DocumentValidationService
        {
            protected function extractWithOcr(string $path, string $mime): ?string
            {
                return null;
            }
        });

        $this->actingAs($user)
            ->from(route('activity-requests.show', $request))
            ->post(route('activity-requests.communication-letter.store', $request), [
                'communication_letter' => $this->pdf(''),
                'signed_confirmation' => '1',
            ])
            ->assertRedirect(route('activity-requests.show', $request));

        $request->refresh();
        $this->assertSame('needs_review', $request->validation_status);
        $this->assertStringContainsString('OCR is unavailable', $request->validation_notes);
        Storage::disk('private')->assertExists($request->communication_letter);
        $this->assertDatabaseHas('workflow_events', [
            'event_type' => 'document_validated',
            'description' => 'Communication letter validation: needs_review',
        ]);
    }

    public function test_workflow_submission_file_validation_records_workflow_event(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['org_name' => 'Campus Council']);
        $workflow = OrganizationWorkflow::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);
        $path = $this->pdf('Campus Council<br>Date: October 28, 2026<br>Approved by: Dean')
            ->store('workflow-submissions', 'private');

        $submission = WorkflowSubmission::create([
            'organization_workflow_id' => $workflow->id,
            'document_type' => OrganizationWorkflow::DOC_ACTIVITY_REQUEST,
            'file_path' => $path,
        ]);

        $this->assertSame('valid', $submission->fresh()->validation_status);
        $this->assertDatabaseHas('workflow_events', [
            'workflow_submission_id' => $submission->id,
            'event_type' => 'document_validated',
        ]);
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

    public function test_only_the_activity_owner_can_view_private_attendance_sheet_and_photo(): void
    {
        Storage::fake('private');
        [$owner, $request] = $this->createActivityRequest();
        $report = ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'activity-documents/narrative-reports/report.pdf',
            'narrative_source' => 'generated',
            'narrative_content' => ['body' => 'Report text'],
            'attendance_sheet_path' => 'activity-documents/attendance-sheets/attendance.pdf',
            'submitted_at' => now(),
        ]);
        $photo = ActivityReportPhoto::create([
            'activity_report_id' => $report->id,
            'path' => 'activity-documents/activity-photos/proof.jpg',
            'sort_order' => 0,
        ]);
        Storage::disk('private')->put($report->attendance_sheet_path, 'attendance');
        Storage::disk('private')->put($photo->path, 'photo');

        $this->actingAs($owner)
            ->get(route('activity-requests.documents.show', [$request, 'attendance-sheet']))
            ->assertOk();
        $this->get(route('activity-requests.report-photos.show', [$request, $photo]))->assertOk();

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)
            ->get(route('activity-requests.documents.show', [$request, 'attendance-sheet']))
            ->assertForbidden();
        $this->get(route('activity-requests.report-photos.show', [$request, $photo]))
            ->assertForbidden();
    }

    private function createActivityRequest(?string $communicationLetter = null): array
    {
        $user = User::factory()->create(['org_name' => 'Campus Organization']);
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
            'signed_by_organization_head' => '1',
            'signed_by_adviser' => '1',
            'signed_by_dean_president' => '1',
        ], $values);
    }

    private function pdf(string $html): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'communication-letter.pdf',
            Pdf::loadHTML('<html><body>' . $html . '</body></html>')->output()
        );
    }
}
