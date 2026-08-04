<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GpoaActivity extends Model
{
    protected $fillable = [
        'gpoa_id',
        'title',
        'date',
        'venue',
        'category',
        'objectives',
        'target_participants',
        'estimated_budget',
        'source_of_funds',
        'person_in_charge',
        'sdgs',
        'preceding_activity',
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

    public function activityRequests(): HasMany
    {
        return $this->hasMany(ActivityRequest::class);
    }
}
