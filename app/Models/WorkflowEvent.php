<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_workflow_id', 'workflow_submission_id',
        'user_id', 'event_type', 'description', 'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(OrganizationWorkflow::class, 'organization_workflow_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(WorkflowSubmission::class, 'workflow_submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
