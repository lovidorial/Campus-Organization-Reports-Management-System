<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'organization', 'date', 'venue',
        'communication_letter', 'narrative_report',
        'description', 'participants_count', 'status',
        'term', 'school_year', 'category', 'basis_grading', 'reject_reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
