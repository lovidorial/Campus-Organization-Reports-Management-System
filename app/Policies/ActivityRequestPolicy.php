<?php

namespace App\Policies;

use App\Models\ActivityRequest;
use App\Models\User;

class ActivityRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActivityRequest $activityRequest): bool
    {
        return $this->allowsOrganizationAccess($user, $activityRequest->user?->organization_id);
    }

    public function update(User $user, ActivityRequest $activityRequest): bool
    {
        return $this->allowsOrganizationAccess($user, $activityRequest->user?->organization_id);
    }

    public function delete(User $user, ActivityRequest $activityRequest): bool
    {
        return $this->allowsOrganizationAccess($user, $activityRequest->user?->organization_id);
    }

    protected function allowsOrganizationAccess(User $user, ?int $organizationId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organizationId !== null && $organizationId !== 0 && (int) $user->organization_id === (int) $organizationId;
    }
}
