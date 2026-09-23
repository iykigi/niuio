<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Application $application): bool
    {
        return $user->id === $application->user_id || $user->can('releases.view');
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function update(User $user, Application $application): bool
    {
        if ($user->id === $application->user_id) {
            return ! $user->is_suspended;
        }

        return $user->can('releases.manage');
    }

    public function delete(User $user, Application $application): bool
    {
        return $this->update($user, $application);
    }
}
