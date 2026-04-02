<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'college', 'sc_president',
        'term', 'school_year', 'description', 'is_active',
    ];

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function activities()
    {
        return $this->hasManyThrough(Activity::class, User::class);
    }
}
