<?php

namespace Tests\Feature;

use App\Models\OrganizationWorkflow;
use App\Models\User;
use App\Models\WorkflowSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowSubmissionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_shows_the_users_submissions_and_excludes_other_organizations(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $reviewer = User::factory()->create(['role' => 'admin', 'name' => 'Review Officer']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $workflow = $this->createWorkflow($user);
        $otherWorkflow = $this->createWorkflow($otherUser);

        WorkflowSubmission::create([
            'organization_workflow_id' => $workflow->id,
            'document_type' => OrganizationWorkflow::DOC_GPOA,
            'version' => 2,
            'status' => WorkflowSubmission::STATUS_APPROVED,
            'submitted_at' => '2026-01-15 13:45:00',
            'reviewed_by' => $reviewer->id,
            'is_current' => true,
        ]);

        WorkflowSubmission::create([
            'organization_workflow_id' => $otherWorkflow->id,
            'document_type' => OrganizationWorkflow::DOC_GPOA,
            'version' => 1,
            'status' => WorkflowSubmission::STATUS_PENDING,
            'submitted_at' => '2026-01-10 09:00:00',
            'is_current' => true,
        ]);

        $response = $this->actingAs($user)->get(route('workflow.submission-history'));

        $response->assertOk();
        $response->assertSee('Review Officer');
        $response->assertSee('Jan 15, 2026 1:45 PM');
        $response->assertSee('v2');
        $response->assertDontSee('Jan 10, 2026 9:00 AM');
    }

    private function createWorkflow(User $user): OrganizationWorkflow
    {
        return OrganizationWorkflow::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'current_stage' => OrganizationWorkflow::STAGE_GPOA_SUBMITTED,
            'completion_percentage' => 0,
            'is_completed' => false,
            'is_locked' => false,
        ]);
    }
}