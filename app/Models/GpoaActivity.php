<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GpoaActivity extends Model
{
    protected static array $monitoringDeadlineCache = [];

    protected $fillable = [
        'gpoa_id',
        'activity_request_id',
        'title',
        'time_frame',
        'date',
        'end_date',
        'start_time',
        'end_time',
        'date_is_month_only',
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
        'archived_at',
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'date_is_month_only' => 'boolean',
        'archived_at' => 'datetime',
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

    public function monitoringResult(): HasOne
    {
        return $this->hasOne(MonitoringResult::class);
    }

    public function scopeWithMonitoringData(Builder $query): Builder
    {
        return $query->with([
            'gpoa',
            'activityRequest.report.photos',
            'activityRequest.programFlows',
            'activityRequest.monitoringResult',
            'monitoringResult',
        ]);
    }

    public function monitoringStatus(): array
    {
        if ($this->archived_at) {
            return ['status' => 'Archived', 'late' => false];
        }

        $letterPresent = filled($this->activityRequest?->communication_letter);
        $report = $this->activityRequest?->report;
        $reportPresent = filled($report?->narrative_report) || filled($report?->narrative_content);
        $requestRejected = $this->activityRequest?->status === 'rejected';

        $status = $requestRejected || ! $letterPresent
            ? 'Pending'
            : ($reportPresent && $report?->status === 'approved' ? 'Completed' : 'Ongoing');

        return [
            'status' => $status,
            'late' => $status !== 'Completed' && $this->isLateForMonitoring(),
        ];
    }

    public function letterStatusLabel(): string
    {
        return filled($this->activityRequest?->communication_letter)
            ? 'Uploaded'
            : 'Pending';
    }

    public function narrativeStatusLabel(): string
    {
        $report = $this->activityRequest?->report;

        if (! $report) {
            return 'Pending';
        }

        if (in_array($report->status, ['approved', 'needs_revision', 'rejected'], true)) {
            return $report->reviewStatusLabel();
        }

        if (filled($report->narrative_report) && ($report->narrative_source === 'generated' || $report->narrative_source === 'uploaded')) {
            return 'For Review';
        }

        if (filled($report->narrative_content)) {
            return 'For Review';
        }

        return 'Pending';
    }

    protected function isLateForMonitoring(): bool
    {
        $today = now()->startOfDay();

        if ($this->date_is_month_only && $this->date) {
            return $this->date->copy()->endOfMonth()->lt($today);
        }

        if ($this->time_frame === 'date_range' && $this->end_date) {
            return $this->end_date->lt($today);
        }

        if ($this->date && $this->date->lt($today)) {
            return true;
        }

        $gpoa = $this->gpoa;
        if (! $gpoa) {
            return false;
        }

        $deadlineTypes = [
            DocumentDeadline::TYPE_ACTIVITY_REQUEST,
            DocumentDeadline::TYPE_ACTIVITY_REPORT,
        ];

        $cacheKey = $gpoa->term . '|' . $gpoa->school_year;
        if (! array_key_exists($cacheKey, self::$monitoringDeadlineCache)) {
            self::$monitoringDeadlineCache[$cacheKey] = DocumentDeadline::query()
                ->whereIn('document_type', $deadlineTypes)
                ->where('term', $gpoa->term)
                ->where('school_year', $gpoa->school_year)
                ->orderByDesc('deadline_date')
                ->first();
        }

        $deadline = self::$monitoringDeadlineCache[$cacheKey];

        if (! $deadline || ! $deadline->deadline_date) {
            return false;
        }

        return $deadline->deadline_date->isPast();
    }
}
