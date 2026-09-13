<?php

namespace App\Policies;

use App\Models\OrganizationMember;
use App\Models\User;

class OrganizationMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || ! empty($user->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ! empty($user->organization_id);
    }

    public function view(User $user, OrganizationMember $member): bool
    {
        return $this->allowsOrganizationAccess($user, $member->organization_id);
    }

    public function update(User $user, OrganizationMember $member): bool
    {
        return $this->allowsOrganizationAccess($user, $member->organization_id);
    }

    public function delete(User $user, OrganizationMember $member): bool
    {
        return $this->allowsOrganizationAccess($user, $member->organization_id);
    }

    protected function allowsOrganizationAccess(User $user, ?int $organizationId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organizationId !== null && $organizationId !== 0 && (int) $user->organization_id === (int) $organizationId;
    }
}
