<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupArchive extends Model
{
    protected $fillable = [
        'filename',
        'last_downloaded_at',
    ];

    protected $casts = [
        'last_downloaded_at' => 'datetime',
    ];
}
