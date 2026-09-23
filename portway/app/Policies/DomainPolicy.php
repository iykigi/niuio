<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

class DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id || $user->can('domains.view');
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function update(User $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id || $user->can('domains.manage');
    }

    public function delete(User $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id || $user->can('domains.manage');
    }
}
