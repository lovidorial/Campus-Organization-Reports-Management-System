<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    use HasFactory;

    protected $table = 'backup_settings';

    public $timestamps = false;

    protected $fillable = [
        'frequency',
        'last_run_at',
        'last_successful_at',
        'last_error',
        'retention_count',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'last_successful_at' => 'datetime',
        'retention_count' => 'integer',
    ];
}
