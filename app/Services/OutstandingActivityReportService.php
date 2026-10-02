<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class OutstandingActivityReportService
{
    public function forUser(User $user): Collection
    {
        if ($user->isAdmin()) {
            return collect();
        }

        return ActivityRequest::query()
            ->where('user_id', $user->id)
            ->with(['gpoaActivity', 'report'])
            ->get()
            ->filter(function (ActivityRequest $activityRequest): bool {
                $plannedActivity = $activityRequest->gpoaActivity;
                if ($plannedActivity?->archived_at) {
                    return false;
                }

                $report = $activityRequest->report;
                if ($report?->status !== 'needs_revision'
                    && (filled($report?->narrative_report) || filled($report?->narrative_content))) {
                    return false;
                }

                $dueDate = $activityRequest->end_date
                    ?? $activityRequest->date
                    ?? $plannedActivity?->end_date
                    ?? $plannedActivity?->date;

                return $dueDate !== null && $dueDate->lte(today());
            })
            ->sortBy(fn (ActivityRequest $activityRequest) => (
                $activityRequest->end_date
                ?? $activityRequest->date
                ?? $activityRequest->gpoaActivity?->end_date
                ?? $activityRequest->gpoaActivity?->date
            )?->timestamp ?? 0)
            ->values();
    }
}
