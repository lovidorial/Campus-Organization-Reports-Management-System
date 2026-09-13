<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Activity $activity): bool
    {
        return $this->allowsOrganizationAccess($user, $activity->user?->organization_id);
    }

    public function update(User $user, Activity $activity): bool
    {
        return $this->allowsOrganizationAccess($user, $activity->user?->organization_id);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->allowsOrganizationAccess($user, $activity->user?->organization_id);
    }

    protected function allowsOrganizationAccess(User $user, ?int $organizationId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organizationId !== null && $organizationId !== 0 && (int) $user->organization_id === (int) $organizationId;
    }
}
