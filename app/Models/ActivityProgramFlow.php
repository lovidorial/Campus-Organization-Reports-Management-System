<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityProgramFlow extends Model
{
    protected $fillable = [
        'activity_request_id',
        'time',
        'flow',
        'person_in_charge',
        'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public function activityRequest(): BelongsTo
    {
        return $this->belongsTo(ActivityRequest::class);
    }
}