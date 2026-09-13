<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

class UserNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserNotification $notification): bool
    {
        return $this->allowsOwnUser($user, $notification->user_id);
    }

    public function update(User $user, UserNotification $notification): bool
    {
        return $this->allowsOwnUser($user, $notification->user_id);
    }

    public function delete(User $user, UserNotification $notification): bool
    {
        return $this->allowsOwnUser($user, $notification->user_id);
    }

    protected function allowsOwnUser(User $user, int $ownerId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $ownerId;
    }
}
