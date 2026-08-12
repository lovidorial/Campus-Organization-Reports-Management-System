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
        'term', 'school_year', 'description', 'is_active',
        'logo_path',
    ];

    protected $appends = ['logo_url'];

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
            return Storage::disk('public')->url($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function activities()
    {
        return $this->hasManyThrough(Activity::class, User::class);
    }
}
