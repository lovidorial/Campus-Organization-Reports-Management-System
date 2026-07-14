<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationWorkflow extends Model
{
    public const STAGE_GPOA_PENDING = 'gpoa_pending';
    public const STAGE_GPOA_SUBMITTED = 'gpoa_submitted';
    public const STAGE_GPOA_APPROVED = 'gpoa_approved';
    public const STAGE_COMM_SUBMITTED = 'comm_submitted';
    public const STAGE_COMM_APPROVED = 'comm_approved';
    public const STAGE_SUMMARY_SUBMITTED = 'summary_submitted';
    public const STAGE_SUMMARY_APPROVED = 'summary_approved';
    public const STAGE_COMPLETED = 'completed';

    public const DOC_GPOA = 'gpoa';
    public const DOC_COMMUNICATION = 'communication_letter';
    public const DOC_SUMMARY = 'summary_report';

    protected $fillable = [
        'user_id', 'term', 'school_year', 'current_stage',
        'completion_percentage', 'is_completed', 'is_locked',
        'reopened_by', 'reopened_at', 'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'is_locked' => 'boolean',
        'reopened_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(WorkflowSubmission::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class)->orderByDesc('created_at');
    }

    public function currentSubmission(string $documentType): ?WorkflowSubmission
    {
        return $this->submissions()
            ->where('document_type', $documentType)
            ->where('is_current', true)
            ->latest()
            ->first();
    }

    public function canSubmitGpoa(): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $current = $this->currentSubmission(self::DOC_GPOA);

        return !$current || in_array($current->status, [
            WorkflowSubmission::STATUS_PENDING,
            WorkflowSubmission::STATUS_REJECTED,
        ]);
    }

    public function canEditGpoa(): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $current = $this->currentSubmission(self::DOC_GPOA);

        return $current && in_array($current->status, [
            WorkflowSubmission::STATUS_SUBMITTED,
            WorkflowSubmission::STATUS_UNDER_REVIEW,
        ]);
    }

    public function canSubmitCommunicationLetter(): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $gpoa = $this->currentSubmission(self::DOC_GPOA);
        if (!$gpoa || $gpoa->status !== WorkflowSubmission::STATUS_APPROVED) {
            return false;
        }

        $comm = $this->currentSubmission(self::DOC_COMMUNICATION);

        return !$comm || $comm->status === WorkflowSubmission::STATUS_REJECTED;
    }

    public function canSubmitSummaryReport(): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $comm = $this->currentSubmission(self::DOC_COMMUNICATION);
        if (!$comm || $comm->status !== WorkflowSubmission::STATUS_APPROVED) {
            return false;
        }

        $summary = $this->currentSubmission(self::DOC_SUMMARY);

        return !$summary || $summary->status === WorkflowSubmission::STATUS_REJECTED;
    }

    public function isGpoaApproved(): bool
    {
        $gpoa = $this->currentSubmission(self::DOC_GPOA);

        return $gpoa && $gpoa->status === WorkflowSubmission::STATUS_APPROVED;
    }

    public function progressStages(): array
    {
        $gpoa = $this->currentSubmission(self::DOC_GPOA);
        $comm = $this->currentSubmission(self::DOC_COMMUNICATION);
        $summary = $this->currentSubmission(self::DOC_SUMMARY);

        return [
            [
                'key' => 'gpoa_submitted',
                'label' => 'GPOA Submitted',
                'status' => $this->stageStatusForSubmission($gpoa, true),
                'submission' => $gpoa,
            ],
            [
                'key' => 'gpoa_approved',
                'label' => 'GPOA Approved',
                'status' => $gpoa && $gpoa->status === WorkflowSubmission::STATUS_APPROVED
                    ? WorkflowSubmission::STATUS_APPROVED
                    : WorkflowSubmission::STATUS_PENDING,
                'submission' => $gpoa,
            ],
            [
                'key' => 'comm_submitted',
                'label' => 'Communication Letter Submitted',
                'status' => $this->stageStatusForSubmission($comm, $this->isGpoaApproved()),
                'submission' => $comm,
                'locked' => !$this->isGpoaApproved(),
            ],
            [
                'key' => 'comm_approved',
                'label' => 'Communication Letter Approved',
                'status' => $comm && $comm->status === WorkflowSubmission::STATUS_APPROVED
                    ? WorkflowSubmission::STATUS_APPROVED
                    : WorkflowSubmission::STATUS_PENDING,
                'submission' => $comm,
                'locked' => !$this->isGpoaApproved(),
            ],
            [
                'key' => 'summary_submitted',
                'label' => 'Summary Report Submitted',
                'status' => $this->stageStatusForSubmission(
                    $summary,
                    $comm && $comm->status === WorkflowSubmission::STATUS_APPROVED
                ),
                'submission' => $summary,
                'locked' => !($comm && $comm->status === WorkflowSubmission::STATUS_APPROVED),
            ],
            [
                'key' => 'summary_approved',
                'label' => 'Summary Report Approved',
                'status' => $summary && $summary->status === WorkflowSubmission::STATUS_APPROVED
                    ? WorkflowSubmission::STATUS_APPROVED
                    : WorkflowSubmission::STATUS_PENDING,
                'submission' => $summary,
                'locked' => !($comm && $comm->status === WorkflowSubmission::STATUS_APPROVED),
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'status' => $this->is_completed
                    ? WorkflowSubmission::STATUS_APPROVED
                    : WorkflowSubmission::STATUS_PENDING,
                'submission' => null,
            ],
        ];
    }

    private function stageStatusForSubmission(?WorkflowSubmission $submission, bool $unlocked): string
    {
        if (!$unlocked) {
            return WorkflowSubmission::STATUS_PENDING;
        }

        if (!$submission) {
            return WorkflowSubmission::STATUS_PENDING;
        }

        return $submission->status;
    }

    public function currentStatusLabel(): string
    {
        if ($this->is_completed) {
            return 'Completed';
        }

        $gpoa = $this->currentSubmission(self::DOC_GPOA);
        $comm = $this->currentSubmission(self::DOC_COMMUNICATION);
        $summary = $this->currentSubmission(self::DOC_SUMMARY);

        if (!$gpoa || $gpoa->status === WorkflowSubmission::STATUS_PENDING) {
            return 'GPOA Not Submitted';
        }

        if (in_array($gpoa->status, [WorkflowSubmission::STATUS_SUBMITTED, WorkflowSubmission::STATUS_UNDER_REVIEW], true)) {
            return 'GPOA Under Review';
        }

        if ($gpoa->status === WorkflowSubmission::STATUS_REJECTED) {
            return 'GPOA Rejected';
        }

        if (!$comm || $comm->status === WorkflowSubmission::STATUS_PENDING) {
            return 'Communication Letter Pending';
        }

        if (in_array($comm->status, [WorkflowSubmission::STATUS_SUBMITTED, WorkflowSubmission::STATUS_UNDER_REVIEW], true)) {
            return 'Communication Letter Under Review';
        }

        if ($comm->status === WorkflowSubmission::STATUS_REJECTED) {
            return 'Communication Letter Rejected';
        }

        if (!$summary || $summary->status === WorkflowSubmission::STATUS_PENDING) {
            return 'Summary Report Pending';
        }

        if (in_array($summary->status, [WorkflowSubmission::STATUS_SUBMITTED, WorkflowSubmission::STATUS_UNDER_REVIEW], true)) {
            return 'Summary Report Under Review';
        }

        if ($summary->status === WorkflowSubmission::STATUS_REJECTED) {
            return 'Summary Report Rejected';
        }

        return 'In Progress';
    }

    public function currentStatusColor(): string
    {
        if ($this->is_completed) {
            return 'green';
        }

        $label = $this->currentStatusLabel();

        if (str_contains($label, 'Rejected')) {
            return 'red';
        }

        if (str_contains($label, 'Under Review')) {
            return 'orange';
        }

        if (str_contains($label, 'Pending') || str_contains($label, 'Not Submitted')) {
            return 'amber';
        }

        return 'blue';
    }

    /**
     * @return array{
     *     type: string,
     *     title: string,
     *     message: string,
     *     submessage: ?string,
     *     action_url: ?string,
     *     action_label: ?string,
     *     deadline: ?\Illuminate\Support\Carbon,
     *     estimated_review: ?string
     * }
     */
    public function currentActionInfo(): array
    {
        if ($this->is_completed) {
            return [
                'type' => 'completed',
                'title' => 'Workflow Complete',
                'message' => 'Congratulations! Your organization has successfully completed all required document submissions.',
                'submessage' => 'All submissions are locked. Contact OSDW if revisions are needed.',
                'action_url' => null,
                'action_label' => null,
                'deadline' => null,
                'estimated_review' => null,
            ];
        }

        if ($this->is_locked) {
            return [
                'type' => 'waiting',
                'title' => 'Current Action',
                'message' => 'Your workflow is currently locked.',
                'submessage' => 'No action is required at this time. Contact OSDW for assistance.',
                'action_url' => null,
                'action_label' => null,
                'deadline' => null,
                'estimated_review' => null,
            ];
        }

        $gpoa = $this->currentSubmission(self::DOC_GPOA);
        $comm = $this->currentSubmission(self::DOC_COMMUNICATION);
        $summary = $this->currentSubmission(self::DOC_SUMMARY);

        if (!$gpoa || $gpoa->status === WorkflowSubmission::STATUS_PENDING) {
            return [
                'type' => 'action_required',
                'title' => 'Action Required',
                'message' => 'Please submit your General Plan of Activities (GPOA) to begin the workflow.',
                'submessage' => 'Your Communication Letter and Summary Report will unlock after GPOA approval.',
                'action_url' => route('gpoa.create'),
                'action_label' => 'Submit GPOA',
                'deadline' => null,
                'estimated_review' => null,
            ];
        }

        if ($gpoa->status === WorkflowSubmission::STATUS_REJECTED) {
            return [
                'type' => 'action_required',
                'title' => 'Action Required',
                'message' => 'Your GPOA was rejected. Please review the feedback and resubmit.',
                'submessage' => $gpoa->reject_reason,
                'action_url' => route('gpoa.create'),
                'action_label' => 'Resubmit GPOA',
                'deadline' => $gpoa->updated_at?->copy()->addDays(14),
                'estimated_review' => null,
            ];
        }

        if (in_array($gpoa->status, [WorkflowSubmission::STATUS_SUBMITTED, WorkflowSubmission::STATUS_UNDER_REVIEW], true)) {
            return [
                'type' => 'waiting',
                'title' => 'Current Action',
                'message' => 'Your GPOA has been submitted successfully.',
                'submessage' => 'It is currently under review by the OSDW Office. No action is required at this time.',
                'action_url' => null,
                'action_label' => null,
                'deadline' => null,
                'estimated_review' => '3–5 Working Days',
            ];
        }

        if ($this->canSubmitCommunicationLetter() && (!$comm || $comm->status === WorkflowSubmission::STATUS_PENDING)) {
            $deadline = $gpoa->approved_at?->copy()->addDays(30);

            return [
                'type' => 'action_required',
                'title' => 'Action Required',
                'message' => 'Please upload your Communication Letter.',
                'submessage' => 'Your GPOA has been approved. Submit your Communication Letter to proceed.',
                'action_url' => route('workflow.communication-letter'),
                'action_label' => 'Upload Communication Letter',
                'deadline' => $deadline,
                'estimated_review' => null,
            ];
        }

        if ($comm && $comm->status === WorkflowSubmission::STATUS_REJECTED) {
            return [
                'type' => 'action_required',
                'title' => 'Action Required',
                'message' => 'Your Communication Letter was rejected. Please review the feedback and resubmit.',
                'submessage' => $comm->reject_reason,
                'action_url' => route('workflow.communication-letter'),
                'action_label' => 'Resubmit Communication Letter',
                'deadline' => $comm->updated_at?->copy()->addDays(14),
                'estimated_review' => null,
            ];
        }

        if ($comm && in_array($comm->status, [WorkflowSubmission::STATUS_SUBMITTED, WorkflowSubmission::STATUS_UNDER_REVIEW], true)) {
            return [
                'type' => 'waiting',
                'title' => 'Current Action',
                'message' => 'Your Communication Letter has been submitted successfully.',
                'submessage' => 'It is currently under review by the OSDW Office. No action is required at this time.',
                'action_url' => null,
                'action_label' => null,
                'deadline' => null,
                'estimated_review' => '3–5 Working Days',
            ];
        }

        if ($this->canSubmitSummaryReport() && (!$summary || $summary->status === WorkflowSubmission::STATUS_PENDING)) {
            $deadline = $comm->approved_at?->copy()->addDays(30);

            return [
                'type' => 'action_required',
                'title' => 'Action Required',
                'message' => 'Please submit your Summary Report.',
                'submessage' => 'Your Communication Letter has been approved. Submit your Summary Report to complete the workflow.',
                'action_url' => route('workflow.summary-report'),
                'action_label' => 'Submit Summary Report',
                'deadline' => $deadline,
                'estimated_review' => null,
            ];
        }

        if ($summary && $summary->status === WorkflowSubmission::STATUS_REJECTED) {
            return [
                'type' => 'action_required',
                'title' => 'Action Required',
                'message' => 'Your Summary Report was rejected. Please review the feedback and resubmit.',
                'submessage' => $summary->reject_reason,
                'action_url' => route('workflow.summary-report'),
                'action_label' => 'Resubmit Summary Report',
                'deadline' => $summary->updated_at?->copy()->addDays(14),
                'estimated_review' => null,
            ];
        }

        if ($summary && in_array($summary->status, [WorkflowSubmission::STATUS_SUBMITTED, WorkflowSubmission::STATUS_UNDER_REVIEW], true)) {
            return [
                'type' => 'waiting',
                'title' => 'Current Action',
                'message' => 'Your Summary Report has been submitted successfully.',
                'submessage' => 'It is currently under review by the OSDW Office. No action is required at this time.',
                'action_url' => null,
                'action_label' => null,
                'deadline' => null,
                'estimated_review' => '3–5 Working Days',
            ];
        }

        return [
            'type' => 'waiting',
            'title' => 'Current Action',
            'message' => 'Your submissions are being processed.',
            'submessage' => 'No action is required at this time.',
            'action_url' => null,
            'action_label' => null,
            'deadline' => null,
            'estimated_review' => null,
        ];
    }
}
