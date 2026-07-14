<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowSubmission extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'organization_workflow_id', 'document_type', 'version', 'gpoa_id',
        'file_path', 'status', 'submitted_at', 'approved_at',
        'reviewed_by', 'approval_remarks', 'reject_reason', 'is_current',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(OrganizationWorkflow::class, 'organization_workflow_id');
    }

    public function gpoa(): BelongsTo
    {
        return $this->belongsTo(Gpoa::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documentLabel(): string
    {
        return match ($this->document_type) {
            OrganizationWorkflow::DOC_GPOA => 'GPOA',
            OrganizationWorkflow::DOC_COMMUNICATION => 'Communication Letter',
            OrganizationWorkflow::DOC_SUMMARY => 'Summary Report',
            default => ucfirst(str_replace('_', ' ', $this->document_type)),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_SUBMITTED => 'blue',
            self::STATUS_UNDER_REVIEW => 'orange',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray',
        };
    }

    public function statusClasses(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-gray-100 text-gray-600 border-gray-200',
            self::STATUS_SUBMITTED => 'bg-blue-100 text-blue-700 border-blue-200',
            self::STATUS_UNDER_REVIEW => 'bg-orange-100 text-orange-700 border-orange-200',
            self::STATUS_APPROVED => 'bg-green-100 text-green-700 border-green-200',
            self::STATUS_REJECTED => 'bg-red-100 text-red-700 border-red-200',
            default => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}
