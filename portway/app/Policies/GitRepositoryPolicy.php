<?php

namespace App\Policies;

use App\Models\GitRepository;
use App\Models\User;

class GitRepositoryPolicy
{
    public function view(User $user, GitRepository $repository): bool
    {
        return $user->id === $repository->site->user_id;
    }

    public function update(User $user, GitRepository $repository): bool
    {
        return $user->id === $repository->site->user_id;
    }

    public function delete(User $user, GitRepository $repository): bool
    {
        return $user->id === $repository->site->user_id;
    }

    public function deploy(User $user, GitRepository $repository): bool
    {
        return ($user->id === $repository->site->user_id && ! $user->is_suspended) || $user->can('sites.manage');
    }
}
