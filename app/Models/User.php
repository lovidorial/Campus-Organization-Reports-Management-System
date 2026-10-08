<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Storage;
use App\Models\OrganizationWorkflow;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const TERM_ENDED_MESSAGE = "This account's term has ended. If you are the outgoing officer, your organization's new Secretary should have received new login credentials from OSDW. Questions? Contact osdwcsuaparri@gmail.com or the CSUAparri-OSDW Facebook page.";

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
                $baseUrl = app()->runningInConsole() ? '' : request()->getBaseUrl();

                return $baseUrl . '/storage/' . ltrim($path, '/');
            }
        }

        return asset('images/osdw.logo.jpg');
    }

    public function gpoas()
    {
        return $this->hasMany(Gpoa::class);
    }

    public function gpoaSubmissionStatus(): array
    {
        if ($this->isAdmin()) {
            return ['allowed' => true, 'blockingGpoa' => null, 'unfinishedCount' => 0];
        }

        $gpoas = $this->gpoas()
            ->with(['activities' => fn ($query) => $query->withMonitoringData()])
            ->orderBy('created_at')
            ->get();

        foreach ($gpoas as $gpoa) {
            $unfinishedCount = $gpoa->activities
                ->filter(fn ($activity) => ! in_array($activity->monitoringStatus()['status'], ['Completed', 'Archived'], true))
                ->count();

            if ($unfinishedCount > 0) {
                return ['allowed' => false, 'blockingGpoa' => $gpoa, 'unfinishedCount' => $unfinishedCount];
            }
        }

        return ['allowed' => true, 'blockingGpoa' => null, 'unfinishedCount' => 0];
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

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTermEnded(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->officer_status === 'archived' || $this->archived_at !== null) {
            return true;
        }

        if (! filled($this->term) || ! filled($this->school_year)) {
            return false;
        }

        $currentPeriod = $this->currentPeriod();
        if (! $currentPeriod) {
            return false;
        }

        return $this->periodKey($this->term, $this->school_year)
            < $this->periodKey($currentPeriod[0], $currentPeriod[1]);
    }

    public function currentPeriod(): ?array
    {
        if ($this->organization && filled($this->organization->term) && filled($this->organization->school_year)) {
            return [$this->organization->term, $this->organization->school_year];
        }

        return $this->currentPeriodWithoutOrganization();
    }

    private function currentPeriodWithoutOrganization(): ?array
    {
        $workflow = OrganizationWorkflow::query()->latest()->first(['term', 'school_year']);
        if ($workflow && filled($workflow->term) && filled($workflow->school_year)) {
            return [$workflow->term, $workflow->school_year];
        }

        $month = now()->month;
        $schoolYearStart = $month >= 8 ? now()->year : now()->year - 1;
        $term = match (true) {
            $month >= 8 => '1st Term',
            $month <= 5 => '2nd Term',
            default => 'Summer',
        };

        return [$term, $schoolYearStart.'-'.($schoolYearStart + 1)];
    }

    private function periodKey(string $term, string $schoolYear): int
    {
        preg_match('/(\d{4})/', $schoolYear, $yearMatch);
        $year = (int) ($yearMatch[1] ?? 0);
        $normalizedTerm = strtolower($term);
        $termRank = match (true) {
            str_contains($normalizedTerm, '1st'), str_contains($normalizedTerm, 'first') => 1,
            str_contains($normalizedTerm, '2nd'), str_contains($normalizedTerm, 'second') => 2,
            str_contains($normalizedTerm, '3rd'), str_contains($normalizedTerm, 'third') => 3,
            str_contains($normalizedTerm, '4th'), str_contains($normalizedTerm, 'fourth') => 4,
            str_contains($normalizedTerm, 'summer') => 5,
            default => 0,
        };

        return ($year * 10) + $termRank;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
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
