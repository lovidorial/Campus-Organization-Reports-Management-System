<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserBackup;

class UserBackupPolicy
{
    public function download(User $user, UserBackup $backup): bool
    {
        return ! $user->isAdmin() && (int) $backup->user_id === (int) $user->id;
    }
}