<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gpoa extends Model
{
    protected $fillable = [
        'user_id', 'term', 'school_year', 'college', 'document_path',
        'status', 'approved_by', 'approved_at', 'stored_at', 'reject_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'stored_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(GpoaActivity::class);
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'stored']);
    }
}
