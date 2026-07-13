<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GpoaActivity extends Model
{
    protected $fillable = [
        'gpoa_id', 'title', 'date', 'venue', 'category',
        'description', 'participants_count', 'basis_grading',
    ];

    protected $casts = [
        'date' => 'date',
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
