<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'profile_photo_path',
        'term', 'school_year', 'sc_president',
        'position', 'org_name', 'org_type', 'college',
        'organization_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

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
}
