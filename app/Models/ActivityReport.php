<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityReport extends Model
{
    protected $fillable = [
        'activity_request_id', 'narrative_report', 'narrative_source', 'narrative_content', 'submitted_at', 'description',
        'signed_by_secretary', 'signed_by_governor', 'signed_by_advisor', 'signed_by_dean_president',
        'signed_by_organization_head',
        'status', 'feedback', 'reviewed_at', 'reviewed_by', 'attendance_sheet_path',
        'narrative_report_size', 'narrative_report_mime_type', 'narrative_report_sha256',
        'attendance_sheet_size', 'attendance_sheet_mime_type', 'attendance_sheet_sha256',
        'validation_status', 'validation_notes', 'validated_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'narrative_content' => 'array',
        'signed_by_secretary' => 'boolean',
        'signed_by_governor' => 'boolean',
        'signed_by_organization_head' => 'boolean',
        'signed_by_advisor' => 'boolean',
        'signed_by_dean_president' => 'boolean',
        'reviewed_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function activityRequest(): BelongsTo
    {
        return $this->belongsTo(ActivityRequest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ActivityReportPhoto::class)->orderBy('sort_order');
    }

    public function reviewStatusLabel(): string
    {
        if (! filled($this->narrative_report) && ! filled($this->narrative_content)) {
            return 'Pending';
        }

        return match ($this->status) {
            'approved' => 'Approved',
            'needs_revision' => 'Needs Revision',
            'rejected' => 'Rejected',
            default => 'For Review',
        };
    }
}
