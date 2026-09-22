<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Sensitive fields such as role, organization_id, officer_status, archived_at,
    // and archived_reason must only ever be set via explicit, authorized controller
    // logic (for example OfficerController archive/restore/onboarding or admin-only
    // flows). They should never be mass-assigned from general user-submitted forms.
    protected $fillable = [
        'name', 'email', 'password', 'role',
        'profile_photo_path',
        'theme_color',
        'term', 'school_year', 'sc_president',
        'position', 'org_name', 'org_type', 'college',
        'username', 'student_number',
        'organization_id',
        'officer_status', 'archived_at', 'archived_reason',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['avatar_url'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'archived_at'       => 'datetime',
        'terms_accepted_at' => 'datetime',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        $photoPath = $this->profile_photo_path;
        $organizationLogo = $this->organization?->logo_path;

        foreach ([$photoPath, $organizationLogo] as $imagePath) {
            if (empty($imagePath)) {
                continue;
            }

            $path = str_replace('\\', '/', $imagePath);
            if (Storage::disk('public')->exists($path)) {
                return rtrim(config('app.url'), '/') . '/storage/' . ltrim($path, '/');
            }
        }

        return asset('images/osdw.logo.jpg');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function gpoas()
    {
        return $this->hasMany(Gpoa::class);
    }

    public function activityRequests()
    {
        return $this->hasMany(ActivityRequest::class);
    }

    public function organizationWorkflows()
    {
        return $this->hasMany(OrganizationWorkflow::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function approvedGpoaForCurrentPeriod(): bool
    {
        $term = $this->term ?? '1st Term';
        $schoolYear = $this->school_year ?? (date('Y') . '-' . (date('Y') + 1));

        return Gpoa::where('user_id', $this->id)
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->whereIn('status', ['approved', 'stored'])
            ->exists();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function scopeActive($query)
    {
        return $query->where('officer_status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('officer_status', 'archived');
    }
}
