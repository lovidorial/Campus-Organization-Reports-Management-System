<?php

namespace App\Services;

use App\Models\Gpoa;
use App\Models\OrganizationWorkflow;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WorkflowEvent;
use App\Models\WorkflowSubmission;

class OrganizationWorkflowService
{
    public function getOrCreateForUser(User $user, ?string $term = null, ?string $schoolYear = null): OrganizationWorkflow
    {
        $term = $term ?? $user->term ?? '1st Term';
        $schoolYear = $schoolYear ?? $user->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        return OrganizationWorkflow::firstOrCreate(
            [
                'user_id' => $user->id,
                'term' => $term,
                'school_year' => $schoolYear,
            ],
            [
                'current_stage' => OrganizationWorkflow::STAGE_GPOA_PENDING,
                'completion_percentage' => 0,
            ]
        );
    }

    public function recordGpoaSubmission(OrganizationWorkflow $workflow, Gpoa $gpoa): WorkflowSubmission
    {
        $this->archiveCurrentSubmission($workflow, OrganizationWorkflow::DOC_GPOA);

        $version = $workflow->submissions()
            ->where('document_type', OrganizationWorkflow::DOC_GPOA)
            ->max('version') + 1;

        $submission = WorkflowSubmission::create([
            'organization_workflow_id' => $workflow->id,
            'document_type' => OrganizationWorkflow::DOC_GPOA,
            'version' => $version ?: 1,
            'gpoa_id' => $gpoa->id,
            'file_path' => $gpoa->document_path,
            'status' => WorkflowSubmission::STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
            'is_current' => true,
        ]);

        $workflow->update([
            'current_stage' => OrganizationWorkflow::STAGE_GPOA_SUBMITTED,
            'completion_percentage' => $this->calculateCompletion($workflow),
        ]);

        $this->logEvent($workflow, $submission, $workflow->user_id, 'submitted', 'GPOA submitted for OSDW review.');
        $this->notifyAdmins('gpoa_submitted', 'New GPOA Submission', "{$workflow->user->org_name} submitted a GPOA for review.");

        return $submission;
    }

    public function recordDocumentSubmission(
        OrganizationWorkflow $workflow,
        string $documentType,
        string $filePath
    ): WorkflowSubmission {
        $this->archiveCurrentSubmission($workflow, $documentType);

        $version = $workflow->submissions()
            ->where('document_type', $documentType)
            ->max('version') + 1;

        $submission = WorkflowSubmission::create([
            'organization_workflow_id' => $workflow->id,
            'document_type' => $documentType,
            'version' => $version ?: 1,
            'file_path' => $filePath,
            'status' => WorkflowSubmission::STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
            'is_current' => true,
        ]);

        $stage = match ($documentType) {
            OrganizationWorkflow::DOC_COMMUNICATION => OrganizationWorkflow::STAGE_COMM_SUBMITTED,
            OrganizationWorkflow::DOC_SUMMARY => OrganizationWorkflow::STAGE_SUMMARY_SUBMITTED,
            default => $workflow->current_stage,
        };

        $workflow->update([
            'current_stage' => $stage,
            'completion_percentage' => $this->calculateCompletion($workflow),
        ]);

        $label = $submission->documentLabel();
        $this->logEvent($workflow, $submission, $workflow->user_id, 'submitted', "{$label} submitted for OSDW review.");
        $this->notifyAdmins('document_submitted', "New {$label}", "{$workflow->user->org_name} submitted a {$label}.");

        return $submission;
    }

    public function approveSubmission(WorkflowSubmission $submission, User $admin, ?string $remarks = null): void
    {
        $submission->update([
            'status' => WorkflowSubmission::STATUS_APPROVED,
            'approved_at' => now(),
            'reviewed_by' => $admin->id,
            'approval_remarks' => $remarks,
            'reject_reason' => null,
        ]);

        $workflow = $submission->workflow;
        $workflow->load('user');

        if ($submission->document_type === OrganizationWorkflow::DOC_GPOA && $submission->gpoa_id) {
            Gpoa::where('id', $submission->gpoa_id)->update([
                'status' => 'stored',
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'stored_at' => now(),
                'reject_reason' => null,
            ]);
        }

        $nextStage = match ($submission->document_type) {
            OrganizationWorkflow::DOC_GPOA => OrganizationWorkflow::STAGE_GPOA_APPROVED,
            OrganizationWorkflow::DOC_COMMUNICATION => OrganizationWorkflow::STAGE_COMM_APPROVED,
            OrganizationWorkflow::DOC_SUMMARY => OrganizationWorkflow::STAGE_SUMMARY_APPROVED,
            default => $workflow->current_stage,
        };

        $updates = [
            'current_stage' => $nextStage,
            'completion_percentage' => $this->calculateCompletion($workflow->fresh()),
        ];

        if ($submission->document_type === OrganizationWorkflow::DOC_SUMMARY) {
            $updates['is_completed'] = true;
            $updates['is_locked'] = true;
            $updates['current_stage'] = OrganizationWorkflow::STAGE_COMPLETED;
            $updates['completed_at'] = now();
            $updates['completion_percentage'] = 100;
        }

        $workflow->update($updates);

        $label = $submission->documentLabel();
        $this->logEvent($workflow, $submission, $admin->id, 'approved', "{$label} approved by OSDW.");
        $this->notifyUser(
            $workflow->user_id,
            'document_approved',
            "{$label} Approved",
            "Your {$label} has been approved." . ($remarks ? " Remarks: {$remarks}" : '')
        );

        if ($submission->document_type === OrganizationWorkflow::DOC_GPOA) {
            $this->notifyUser(
                $workflow->user_id,
                'stage_unlocked',
                'Communication Letter Unlocked',
                'Your GPOA has been approved. You may now submit your Communication Letter.'
            );
        }

        if ($submission->document_type === OrganizationWorkflow::DOC_COMMUNICATION) {
            $this->notifyUser(
                $workflow->user_id,
                'stage_unlocked',
                'Summary Report Unlocked',
                'Your Communication Letter has been approved. You may now submit your Summary Report.'
            );
        }

        if ($submission->document_type === OrganizationWorkflow::DOC_SUMMARY) {
            $this->notifyUser(
                $workflow->user_id,
                'workflow_completed',
                'Workflow Completed',
                'Congratulations! Your organization has successfully completed all required document submissions.'
            );
        }
    }

    public function rejectSubmission(WorkflowSubmission $submission, User $admin, ?string $reason = null): void
    {
        $submission->update([
            'status' => WorkflowSubmission::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'reject_reason' => $reason,
            'approval_remarks' => null,
        ]);

        $workflow = $submission->workflow;

        if ($submission->document_type === OrganizationWorkflow::DOC_GPOA && $submission->gpoa_id) {
            Gpoa::where('id', $submission->gpoa_id)->update([
                'status' => 'rejected',
                'reject_reason' => $reason,
            ]);
        }

        $workflow->update([
            'completion_percentage' => $this->calculateCompletion($workflow->fresh()),
        ]);

        $label = $submission->documentLabel();
        $this->logEvent($workflow, $submission, $admin->id, 'rejected', "{$label} rejected by OSDW.");
        $this->notifyUser(
            $workflow->user_id,
            'document_rejected',
            "{$label} Rejected",
            "Your {$label} was rejected." . ($reason ? " Reason: {$reason}" : ' Please revise and resubmit.')
        );
    }

    public function reopenWorkflow(OrganizationWorkflow $workflow, User $admin): void
    {
        $workflow->update([
            'is_locked' => false,
            'is_completed' => false,
            'reopened_by' => $admin->id,
            'reopened_at' => now(),
            'completed_at' => null,
            'current_stage' => OrganizationWorkflow::STAGE_SUMMARY_APPROVED,
            'completion_percentage' => $this->calculateCompletion($workflow->fresh()),
        ]);

        $this->logEvent($workflow, null, $admin->id, 'reopened', 'Workflow reopened by OSDW administrator.');
        $this->notifyUser(
            $workflow->user_id,
            'workflow_reopened',
            'Workflow Reopened',
            'Your organizational workflow has been reopened by OSDW. You may submit revisions as needed.'
        );
    }

    public function calculateCompletion(OrganizationWorkflow $workflow): int
    {
        $stages = $workflow->progressStages();
        $completed = 0;

        foreach ($stages as $stage) {
            if (in_array($stage['status'], [
                WorkflowSubmission::STATUS_SUBMITTED,
                WorkflowSubmission::STATUS_UNDER_REVIEW,
                WorkflowSubmission::STATUS_APPROVED,
            ], true)) {
                $completed++;
            }
        }

        return (int) round(($completed / count($stages)) * 100);
    }

    public function workflowStats(): array
    {
        return [
            'total' => OrganizationWorkflow::count(),
            'completed' => OrganizationWorkflow::where('is_completed', true)->count(),
            'pending' => OrganizationWorkflow::where('is_completed', false)
                ->where('current_stage', '!=', OrganizationWorkflow::STAGE_GPOA_PENDING)
                ->count(),
            'not_started' => OrganizationWorkflow::where('current_stage', OrganizationWorkflow::STAGE_GPOA_PENDING)->count(),
            'overdue' => OrganizationWorkflow::where('is_completed', false)
                ->where('updated_at', '<', now()->subDays(30))
                ->count(),
        ];
    }

    private function archiveCurrentSubmission(OrganizationWorkflow $workflow, string $documentType): void
    {
        $workflow->submissions()
            ->where('document_type', $documentType)
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }

    private function logEvent(
        OrganizationWorkflow $workflow,
        ?WorkflowSubmission $submission,
        ?int $userId,
        string $eventType,
        string $description,
        array $metadata = []
    ): void {
        WorkflowEvent::create([
            'organization_workflow_id' => $workflow->id,
            'workflow_submission_id' => $submission?->id,
            'user_id' => $userId,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }

    private function notifyUser(int $userId, string $type, string $title, string $message): void
    {
        UserNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function notifyAdmins(string $type, string $title, string $message): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
            ]);
        }
    }
}
