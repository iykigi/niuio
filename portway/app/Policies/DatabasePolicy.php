<?php

namespace App\Policies;

use App\Models\Database;
use App\Models\User;

class DatabasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Database $database): bool
    {
        return $user->id === $database->user_id || $user->can('databases.view');
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function update(User $user, Database $database): bool
    {
        return $user->id === $database->user_id || $user->can('databases.manage');
    }

    public function delete(User $user, Database $database): bool
    {
        return $user->id === $database->user_id || $user->can('databases.manage');
    }

    public function runQuery(User $user, Database $database): bool
    {
        return $user->id === $database->user_id || $user->can('databases.manage');
    }
}
