<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpoaModificationRequest extends Model
{
    protected $fillable = [
        'gpoa_id', 'gpoa_activity_id', 'type', 'payload', 'remarks',
        'status', 'requested_by', 'reviewed_by',
    ];

    protected $casts = ['payload' => 'array'];

    public const TYPE_ADD = 'add';
    public const TYPE_REMOVE = 'remove';
    public const TYPE_EDIT = 'edit';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function gpoa(): BelongsTo { return $this->belongsTo(Gpoa::class); }
    public function activity(): BelongsTo { return $this->belongsTo(GpoaActivity::class, 'gpoa_activity_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}