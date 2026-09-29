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

    public function test_workflow_submissions_keep_audit_metadata_and_stay_scoped_to_their_organization(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $reviewer = User::factory()->create(['role' => 'admin', 'name' => 'Review Officer']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $workflow = $this->createWorkflow($user);
        $otherWorkflow = $this->createWorkflow($otherUser);

        $reviewedSubmission = WorkflowSubmission::create([
            'organization_workflow_id' => $workflow->id,
            'document_type' => OrganizationWorkflow::DOC_GPOA,
            'version' => 2,
            'status' => WorkflowSubmission::STATUS_APPROVED,
            'submitted_at' => '2026-01-15 13:45:00',
            'reviewed_by' => $reviewer->id,
            'approval_remarks' => 'Updated schedule approved.',
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

        $this->assertSame(2, $workflow->fresh()->currentSubmission(OrganizationWorkflow::DOC_GPOA)?->version);
        $this->assertSame('Review Officer', $reviewedSubmission->fresh()->reviewer->name);
        $this->assertSame('Updated schedule approved.', $reviewedSubmission->fresh()->approval_remarks);
        $this->assertEquals(1, $workflow->fresh()->submissions()->count());
        $this->assertEquals(1, $otherWorkflow->fresh()->submissions()->count());
        $this->assertNotSame($workflow->id, $otherWorkflow->id);
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