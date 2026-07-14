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
}
