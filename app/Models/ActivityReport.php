<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityReport extends Model
{
    protected $fillable = [
        'activity_request_id', 'narrative_report', 'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function activityRequest(): BelongsTo
    {
        return $this->belongsTo(ActivityRequest::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ActivityReportPhoto::class)->orderBy('sort_order');
    }
}
