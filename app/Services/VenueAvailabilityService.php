<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\Venue;
use Illuminate\Support\Carbon;

class VenueAvailabilityService
{
    public function resolveVenue(string $name): Venue
    {
        $normalizedName = trim($name);
        $venue = Venue::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($normalizedName)])
            ->first();

        return $venue ?? Venue::create([
            'name' => $normalizedName,
            'is_active' => true,
        ]);
    }

    public function conflictingRequest(array $activity, ?int $exceptId = null): ?ActivityRequest
    {
        if (empty($activity['venue_id']) || empty($activity['date'])) {
            return null;
        }

        $startDate = Carbon::parse($activity['date'])->toDateString();
        $endDate = Carbon::parse($activity['end_date'] ?? $activity['date'])->toDateString();

        $query = ActivityRequest::query()
            ->where(function ($query) use ($activity) {
                $query->where('venue_id', $activity['venue_id']);
                if (! empty($activity['venue'])) {
                    $query->orWhere(function ($legacyQuery) use ($activity) {
                        $legacyQuery->whereNull('venue_id')
                            ->whereRaw('LOWER(TRIM(venue)) = ?', [mb_strtolower(trim($activity['venue']))]);
                    });
                }
            })
            ->whereNotIn('status', ['cancelled', 'deleted'])
            ->whereDate('date', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate);
            });

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        foreach ($query->get() as $existing) {
            if ($this->timesOverlap($activity, $existing)) {
                return $existing;
            }
        }

        return null;
    }

    private function timesOverlap(array $activity, ActivityRequest $existing): bool
    {
        $newStart = $activity['start_time'] ?? null;
        $newEnd = $activity['end_time'] ?? null;
        $existingStart = $existing->start_time;
        $existingEnd = $existing->end_time;

        if (! $newStart || ! $newEnd || ! $existingStart || ! $existingEnd) {
            return true;
        }

        return substr((string) $existingStart, 0, 5) < substr((string) $newEnd, 0, 5)
            && substr((string) $existingEnd, 0, 5) > substr((string) $newStart, 0, 5);
    }
}