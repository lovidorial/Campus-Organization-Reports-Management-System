<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationClassification extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_name',
        'aliases',
        'classification',
        'college_area',
    ];

    protected $casts = [
        'aliases' => 'array',
    ];
}
