<?php

namespace App\Policies;

use App\Models\EnvironmentVariable;
use App\Models\User;

class EnvironmentVariablePolicy
{
    public function view(User $user, EnvironmentVariable $variable): bool
    {
        return $user->id === $variable->site->user_id;
    }

    public function update(User $user, EnvironmentVariable $variable): bool
    {
        return $user->id === $variable->site->user_id;
    }

    public function delete(User $user, EnvironmentVariable $variable): bool
    {
        return $user->id === $variable->site->user_id;
    }

    public function reveal(User $user, EnvironmentVariable $variable): bool
    {
        return $user->id === $variable->site->user_id;
    }
}
