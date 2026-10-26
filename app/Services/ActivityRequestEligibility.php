<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class ActivityRequestEligibility
{
    public function pendingNarratives(User $user, string $term, string $schoolYear): Collection
    {
        if ($user->isAdmin()) {
            return collect();
        }

        $organizationUserIds = User::query()
            ->when(
                $user->organization_id,
                fn ($query) => $query->where('organization_id', $user->organization_id),
                fn ($query) => $query->whereKey($user->id)
            )
            ->pluck('id');

        $requests = ActivityRequest::query()
            ->whereIn('user_id', $organizationUserIds)
            ->whereNotIn('status', ['rejected', 'cancelled', 'archived'])
            ->whereDoesntHave('report', fn ($query) => $query->whereNotNull('submitted_at'))
            ->where(function ($query) use ($term, $schoolYear) {
                $query->whereHas('gpoa', fn ($gpoaQuery) => $gpoaQuery
                    ->where('term', $term)
                    ->where('school_year', $schoolYear))
                    ->orWhereHas('gpoaActivity.gpoa', fn ($gpoaQuery) => $gpoaQuery
                        ->where('term', $term)
                        ->where('school_year', $schoolYear))
                    ->orWhere(function ($unscopedQuery) {
                        $unscopedQuery->whereNull('gpoa_id')->whereNull('gpoa_activity_id');
                    });
            })
            ->with(['gpoaActivity', 'report'])
            ->get();

        if (config('activity_requests.strictness', 'any') === 'after_date') {
            $requests = $requests->filter(function (ActivityRequest $activityRequest): bool {
                $activityDate = $activityRequest->date ?? $activityRequest->gpoaActivity?->date;

                return $activityDate !== null && $activityDate->lt(today());
            });
        }

        return $requests
            ->sortBy(fn (ActivityRequest $activityRequest) => (
                $activityRequest->date ?? $activityRequest->gpoaActivity?->date
            )?->timestamp ?? PHP_INT_MAX)
            ->values();
    }
}