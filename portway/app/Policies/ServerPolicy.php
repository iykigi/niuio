<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('servers.view');
    }

    public function view(User $user, Server $server): bool
    {
        return $user->can('servers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('servers.manage');
    }

    public function update(User $user, Server $server): bool
    {
        return $user->can('servers.manage');
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->can('servers.manage');
    }
}
