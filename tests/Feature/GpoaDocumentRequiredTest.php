<?php

namespace Tests\Feature;

use App\Models\Gpoa;
use App\Models\OrganizationWorkflow;
use App\Models\User;
use App\Models\WorkflowSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpoaDocumentRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_gpoa_store_accepts_submission_without_document_upload(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'planned_activities' => [[
                'title' => 'Student Leadership Summit',
                'date' => '2026-10-01',
                'venue' => 'Main Hall',
                'category' => 'Symposium',
                'sdgs' => [4],
            ]],
            'verify' => '1',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('gpoas', [
            'user_id' => $user->id,
            'document_path' => null,
        ]);
    }

    public function test_gpoa_store_rejects_an_incomplete_planned_activity_row(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'planned_activities' => [
                [
                    'title' => 'Student Leadership Summit',
                    'date' => '2026-10-01',
                    'venue' => 'Main Hall',
                    'category' => 'Symposium',
                    'sdgs' => [4],
                ],
                [
                    'sdgs' => [3],
                ],
            ],
            'verify' => '1',
        ]);

        $response->assertSessionHasErrors('planned_activities.1');
        $this->assertStringContainsString(
            'Activity 2 is incomplete',
            $response->getSession()->get('errors')->get('planned_activities.1')[0]
        );
        $this->assertDatabaseCount('gpoas', 0);
    }

    public function test_gpoa_update_keeps_existing_document_when_no_new_file_is_uploaded(): void
    {
        $organization = \App\Models\Organization::create([
            'name' => 'CICS SC',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'org_type' => $organization->type,
            'college' => $organization->college,
        ]);

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'document_path' => 'uploads/gpoa/original.pdf',
            'prepared_by' => 'Jane Doe',
            'status' => 'pending',
        ]);

        $workflow = OrganizationWorkflow::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'current_stage' => OrganizationWorkflow::STAGE_GPOA_SUBMITTED,
            'completion_percentage' => 0,
            'is_completed' => false,
            'is_locked' => false,
        ]);

        WorkflowSubmission::create([
            'organization_workflow_id' => $workflow->id,
            'document_type' => OrganizationWorkflow::DOC_GPOA,
            'version' => 1,
            'gpoa_id' => $gpoa->id,
            'file_path' => $gpoa->document_path,
            'status' => WorkflowSubmission::STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
            'is_current' => true,
        ]);

        $response = $this->actingAs($user)->put(route('gpoa.update', $gpoa), [
            'colleges' => 'CICS',
            'prepared_by' => 'Jane Doe',
            'planned_activities' => [[
                'title' => 'Student Leadership Summit',
                'date' => '2026-10-01',
                'venue' => 'Main Hall',
                'category' => 'Symposium',
                'sdgs' => [4],
            ]],
            'verify' => '1',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('uploads/gpoa/original.pdf', $gpoa->fresh()->document_path);
    }
}
