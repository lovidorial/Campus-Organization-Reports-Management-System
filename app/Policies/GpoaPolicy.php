<?php

namespace App\Policies;

use App\Models\Gpoa;
use App\Models\User;

class GpoaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gpoa $gpoa): bool
    {
        return $this->allowsOrganizationAccess($user, $gpoa->user?->organization_id);
    }

    public function update(User $user, Gpoa $gpoa): bool
    {
        return $this->allowsOrganizationAccess($user, $gpoa->user?->organization_id);
    }

    public function delete(User $user, Gpoa $gpoa): bool
    {
        return $this->allowsOrganizationAccess($user, $gpoa->user?->organization_id);
    }

    protected function allowsOrganizationAccess(User $user, ?int $organizationId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organizationId !== null && $organizationId !== 0 && (int) $user->organization_id === (int) $organizationId;
    }
}
