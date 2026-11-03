<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReportPhoto extends Model
{
    protected $fillable = [
        'activity_report_id',
        'path',
        'caption',
        'sort_order',
        'file_size', 'mime_type', 'sha256',
    ];

    public function activityReport(): BelongsTo
    {
        return $this->belongsTo(ActivityReport::class);
    }
}
