<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GpoaActivity extends Model
{
    protected $fillable = [
        'gpoa_id',
        'activity_request_id',
        'title',
        'date',
        'venue',
        'category',
        'objectives',
        'expected_outcome',
        'target_participants',
        'estimated_budget',
        'source_of_funds',
        'person_in_charge',
        'sdgs',
        'preceding_activity',
        'plan_key_strategy',
        'facilities_materials',
        'remarks',
        'activity_level',
    ];

    protected $casts = [
        'date' => 'date',
        'sdgs' => 'array',
        'estimated_budget' => 'decimal:2',
    ];

    public function gpoa(): BelongsTo
    {
        return $this->belongsTo(Gpoa::class);
    }

    public function activityRequest(): BelongsTo
    {
        return $this->belongsTo(ActivityRequest::class);
    }

    public function activityRequests(): HasMany
    {
        return $this->hasMany(ActivityRequest::class);
    }

    public function monitoringResult(): HasOne
    {
        return $this->hasOne(MonitoringResult::class);
    }

    public function scopeWithMonitoringData(Builder $query): Builder
    {
        return $query->with([
            'gpoa',
            'activityRequest.report',
        ]);
    }

    public static function withMonitoringDataLoaded(): Builder
    {
        return static::query()->withMonitoringData();
    }

    public function monitoringStatus(): array
    {
        $letterPresent = filled($this->activityRequest?->communication_letter);
        $reportPresent = filled($this->activityRequest?->report?->narrative_report)
            || filled($this->activityRequest?->report?->narrative_content);

        if (! $letterPresent && ! $reportPresent) {
            $status = 'Not Started';
        } elseif ($letterPresent xor $reportPresent) {
            $status = 'Ongoing';
        } else {
            $status = 'Completed';
        }

        return [
            'status' => $status,
            'late' => $status !== 'Completed' && $this->isLateForMonitoring(),
        ];
    }

    public function letterStatusLabel(): string
    {
        return filled($this->activityRequest?->communication_letter)
            ? 'Uploaded (check)'
            : 'Pending';
    }

    public function narrativeStatusLabel(): string
    {
        $report = $this->activityRequest?->report;

        if (! $report) {
            return 'Pending';
        }

        if (filled($report->narrative_report) && ($report->narrative_source === 'generated' || $report->narrative_source === 'uploaded')) {
            return $report->narrative_source === 'generated' ? 'Created (check)' : 'Uploaded (check)';
        }

        if (filled($report->narrative_content)) {
            return 'Created (check)';
        }

        return 'Pending';
    }

    protected function isLateForMonitoring(): bool
    {
        $today = now()->startOfDay();

        if ($this->date && $this->date->lt($today)) {
            return true;
        }

        $gpoa = $this->gpoa()->first();
        if (! $gpoa) {
            return false;
        }

        $deadlineTypes = [
            DocumentDeadline::TYPE_ACTIVITY_REQUEST,
            DocumentDeadline::TYPE_ACTIVITY_REPORT,
        ];

        $deadline = DocumentDeadline::query()
            ->whereIn('document_type', $deadlineTypes)
            ->where('term', $gpoa->term)
            ->where('school_year', $gpoa->school_year)
            ->orderByDesc('deadline_date')
            ->first();

        if (! $deadline || ! $deadline->deadline_date) {
            return false;
        }

        return $deadline->deadline_date->isPast();
    }
}
