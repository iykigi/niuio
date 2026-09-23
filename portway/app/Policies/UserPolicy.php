<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->can('users.view');
    }

    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->can('users.edit');
    }

    public function suspend(User $user, User $target): bool
    {
        return $user->can('users.suspend') && $user->id !== $target->id && ! $target->hasRole('Super Admin');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.delete') && $user->id !== $target->id && ! $target->hasRole('Super Admin');
    }

    public function changeRole(User $user, User $target): bool
    {
        return $user->can('users.change_role') && $user->id !== $target->id;
    }

    public function impersonate(User $user, User $target): bool
    {
        return $user->can('users.impersonate') && $user->id !== $target->id && ! $target->isStaff();
    }
}
