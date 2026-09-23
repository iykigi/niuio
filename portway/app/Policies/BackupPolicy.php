<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

class BackupPolicy
{
    public function view(User $user, Backup $backup): bool
    {
        return $user->id === $backup->user_id || $user->can('backups.view');
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function restore(User $user, Backup $backup): bool
    {
        return $user->id === $backup->user_id || $user->can('backups.manage');
    }

    public function delete(User $user, Backup $backup): bool
    {
        return $user->id === $backup->user_id || $user->can('backups.manage');
    }

    public function download(User $user, Backup $backup): bool
    {
        return $user->id === $backup->user_id || $user->can('backups.manage');
    }
}
