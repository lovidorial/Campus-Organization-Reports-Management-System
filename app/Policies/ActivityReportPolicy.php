<?php

namespace App\Policies;

use App\Models\ActivityReport;
use App\Models\User;

class ActivityReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActivityReport $activityReport): bool
    {
        return $this->allowsOrganizationAccess($user, $activityReport->activityRequest?->user?->organization_id);
    }

    public function update(User $user, ActivityReport $activityReport): bool
    {
        return $this->allowsOrganizationAccess($user, $activityReport->activityRequest?->user?->organization_id);
    }

    public function review(User $user, ActivityReport $activityReport): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ActivityReport $activityReport): bool
    {
        return $this->allowsOrganizationAccess($user, $activityReport->activityRequest?->user?->organization_id);
    }

    protected function allowsOrganizationAccess(User $user, ?int $organizationId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organizationId !== null && $organizationId !== 0 && (int) $user->organization_id === (int) $organizationId;
    }
}
