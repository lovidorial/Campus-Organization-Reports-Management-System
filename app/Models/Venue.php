<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Venue extends Model
{
    protected $fillable = ['name', 'capacity', 'is_active'];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function activityRequests(): HasMany
    {
        return $this->hasMany(ActivityRequest::class);
    }

    public function scheduledRequests(): HasMany
    {
        $today = Carbon::today()->toDateString();

        return $this->activityRequests()->where(function ($query) use ($today) {
            $query->where('status', ActivityRequest::STATUS_IN_PROGRESS)
                ->orWhere(function ($query) use ($today) {
                    $query->where('status', ActivityRequest::STATUS_APPROVED)
                        ->whereDate('date', '<=', $today)
                        ->where(function ($query) use ($today) {
                            $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                        });
                });
        });
    }

    public function futureReservationRequests(): HasMany
    {
        return $this->activityRequests()
            ->whereIn('status', [ActivityRequest::STATUS_PENDING, ActivityRequest::STATUS_APPROVED])
            ->whereDate('date', '>', Carbon::today()->toDateString());
    }

    public function getAvailabilityStatusAttribute(): string
    {
        $scheduledCount = $this->getAttribute('scheduled_requests_count')
            ?? $this->scheduledRequests()->count();

        if ($scheduledCount > 0) {
            return 'Scheduled';
        }

        $futureReservationCount = $this->getAttribute('future_reservation_requests_count')
            ?? $this->futureReservationRequests()->count();

        return $futureReservationCount > 0 ? 'Reserved' : 'Available';
    }
}