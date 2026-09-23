<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Site $site): bool
    {
        return $user->id === $site->user_id || $user->can('sites.view');
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function update(User $user, Site $site): bool
    {
        return $user->id === $site->user_id || $user->can('sites.manage');
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->id === $site->user_id || $user->can('sites.manage');
    }

    public function suspend(User $user, Site $site): bool
    {
        return $user->can('sites.suspend');
    }

    public function useTerminal(User $user, Site $site): bool
    {
        return ($user->id === $site->user_id && ! $user->is_suspended) || $user->can('sites.manage');
    }
}
