<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gpoa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ActivityRequest extends Model
{
    protected $fillable = [
        'user_id', 'gpoa_id', 'gpoa_activity_id', 'venue_id', 'title', 'date', 'end_date', 'start_time', 'end_time', 'venue',
        'category', 'sdgs', 'objectives', 'expected_outcome',
        'plan_key_strategy', 'target_participants', 'person_in_charge',
        'facilities_materials', 'estimated_budget', 'remarks', 'source_of_funds',
        'preceding_activity', 'description', 'participants_count',
        'communication_letter', 'status', 'reject_reason',
        'communication_letter_signed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'sdgs' => 'array',
        'estimated_budget' => 'decimal:2',
        'communication_letter_signed_at' => 'datetime',
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

    public function venueRecord(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    public function gpoaActivity(): BelongsTo
    {
        return $this->belongsTo(GpoaActivity::class);
    }

    public function programFlows(): HasMany
    {
        return $this->hasMany(ActivityProgramFlow::class)->orderBy('sort_order');
    }

    public function replaceProgramFlows(array $rows): void
    {
        $this->programFlows()->delete();

        foreach (array_values($rows) as $sortOrder => $row) {
            $this->programFlows()->create([
                'time' => $row['time'],
                'flow' => $row['flow'],
                'person_in_charge' => $row['person_in_charge'],
                'sort_order' => $sortOrder,
            ]);
        }
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

    public function getIsMultiDayAttribute(): bool
    {
        return (bool) ($this->end_date && $this->end_date->ne($this->date));
    }

    public function getDateRangeLabelAttribute(): string
    {
        if (! $this->date) {
            return '—';
        }

        if (! $this->is_multi_day) {
            return $this->date->format('M d, Y');
        }

        if ($this->date->format('M') === $this->end_date->format('M')) {
            return $this->date->format('M d') . '–' . $this->end_date->format('d, Y');
        }

        return $this->date->format('M d, Y') . ' – ' . $this->end_date->format('M d, Y');
    }

}
