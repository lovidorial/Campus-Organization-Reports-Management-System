<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'college', 'sc_president',
        'term', 'school_year', 'description', 'is_active', 'storage_limit_mb',
        'logo_path', 'theme_color',
    ];

    protected $appends = ['logo_url'];

    protected $casts = [
        'storage_limit_mb' => 'integer',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        $path = str_replace('\\', '/', $this->logo_path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            $baseUrl = app()->runningInConsole() ? '' : request()->getBaseUrl();

            return $baseUrl . '/storage/' . ltrim($path, '/');
        }

        return null;
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function organizationMembers()
    {
        return $this->hasMany(OrganizationMember::class)->orderBy('display_order')->orderBy('name');
    }

    public function activities()
    {
        return $this->hasManyThrough(Activity::class, User::class);
    }
}
