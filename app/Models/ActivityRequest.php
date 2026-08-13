<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gpoa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ActivityRequest extends Model
{
    protected $fillable = [
        'user_id', 'gpoa_id', 'gpoa_activity_id', 'title', 'date', 'venue',
        'category', 'sdgs', 'objectives', 'expected_outcome',
        'plan_key_strategy', 'target_participants', 'person_in_charge',
        'facilities_materials', 'estimated_budget', 'remarks', 'source_of_funds',
        'preceding_activity', 'description', 'participants_count',
        'communication_letter', 'status', 'reject_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'sdgs' => 'array',
        'estimated_budget' => 'decimal:2',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_AWAITING_REPORT = 'awaiting_report';
    public const STATUS_REPORT_SUBMITTED = 'report_submitted';
    public const STATUS_CLOSED = 'closed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gpoa(): BelongsTo
    {
        return $this->belongsTo(Gpoa::class);
    }

    public function gpoaActivity(): BelongsTo
    {
        return $this->belongsTo(GpoaActivity::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(ActivityReport::class);
    }

    public function monitoringResult(): HasOne
    {
        return $this->hasOne(MonitoringResult::class);
    }

    public function getGpoaAttribute(): ?Gpoa
    {
        if ($this->relationLoaded('gpoa')) {
            return $this->getRelation('gpoa');
        }

        if ($this->gpoa_id) {
            return $this->belongsTo(Gpoa::class)->getResults();
        }

        return $this->gpoaActivity?->gpoa;
    }

    public function refreshLifecycleStatus(): void
    {
        if ($this->status === self::STATUS_APPROVED && $this->date->lte(now()->startOfDay())) {
            $this->update(['status' => self::STATUS_IN_PROGRESS]);
        }

        if ($this->status === self::STATUS_IN_PROGRESS && $this->date->lt(now()->startOfDay())) {
            $this->update(['status' => self::STATUS_AWAITING_REPORT]);
        }
    }

    public function matchesGpoaLineItem(): bool
    {
        $line = $this->gpoaActivity;
        if (!$line) {
            return false;
        }

        return $line->title === $this->title
            && $line->date->toDateString() === $this->date->toDateString()
            && $line->venue === $this->venue;
    }
}
