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
        if ($user->id === $activityRequest->user_id) {
            return true;
        }

        return $this->allowsOrganizationAccess($user, $activityRequest->user?->organization_id);
    }

    public function update(User $user, ActivityRequest $activityRequest): bool
    {
        if ($user->id === $activityRequest->user_id) {
            return true;
        }

        return $this->allowsOrganizationAccess($user, $activityRequest->user?->organization_id);
    }

    public function uploadReservationSlip(User $user, ActivityRequest $activityRequest): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        $canAccessRequest = $user->id === $activityRequest->user_id
            || $this->allowsOrganizationAccess($user, $activityRequest->user?->organization_id);

        return $canAccessRequest
            && filled($activityRequest->communication_letter)
            && in_array($activityRequest->status, [
                ActivityRequest::STATUS_APPROVED,
                ActivityRequest::STATUS_IN_PROGRESS,
                ActivityRequest::STATUS_AWAITING_REPORT,
                ActivityRequest::STATUS_REPORT_SUBMITTED,
                ActivityRequest::STATUS_CLOSED,
            ], true);
    }

    public function delete(User $user, ActivityRequest $activityRequest): bool
    {
        if ($user->id === $activityRequest->user_id) {
            return true;
        }

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
