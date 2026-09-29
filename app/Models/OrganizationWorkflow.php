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
    public const DOC_ACTIVITY_REQUEST = 'activity_request';
    public const DOC_ACTIVITY_REPORT = 'activity_report';
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

    public function documentDeadline(string $documentType): ?\Illuminate\Support\Carbon
    {
        return DocumentDeadline::forPeriod($documentType, $this->term, $this->school_year)?->deadline_date;
    }

}
