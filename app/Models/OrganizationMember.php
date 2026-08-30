<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'position',
        'year_level',
        'facebook_url',
        'contact_info',
        'photo_path',
        'display_order',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
