<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityReport extends Model
{
    protected $fillable = [
        'activity_request_id', 'narrative_report', 'submitted_at', 'description',
        'signed_by_secretary', 'signed_by_governor', 'signed_by_advisor', 'signed_by_dean_president',
        'status', 'feedback', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'signed_by_secretary' => 'boolean',
        'signed_by_governor' => 'boolean',
        'signed_by_advisor' => 'boolean',
        'signed_by_dean_president' => 'boolean',
        'reviewed_at' => 'datetime',
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
