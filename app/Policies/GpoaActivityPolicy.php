<?php

namespace App\Policies;

use App\Models\GpoaActivity;
use App\Models\User;

class GpoaActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GpoaActivity $gpoaActivity): bool
    {
        return $this->allowsOrganizationAccess($user, $gpoaActivity->gpoa?->user?->organization_id);
    }

    public function update(User $user, GpoaActivity $gpoaActivity): bool
    {
        return $this->allowsOrganizationAccess($user, $gpoaActivity->gpoa?->user?->organization_id);
    }

    public function delete(User $user, GpoaActivity $gpoaActivity): bool
    {
        return $this->allowsOrganizationAccess($user, $gpoaActivity->gpoa?->user?->organization_id);
    }

    protected function allowsOrganizationAccess(User $user, ?int $organizationId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $organizationId !== null && $organizationId !== 0 && (int) $user->organization_id === (int) $organizationId;
    }
}
