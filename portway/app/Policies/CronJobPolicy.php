<?php

namespace App\Policies;

use App\Models\CronJob;
use App\Models\User;

class CronJobPolicy
{
    public function view(User $user, CronJob $cronJob): bool
    {
        return $user->id === $cronJob->user_id;
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function update(User $user, CronJob $cronJob): bool
    {
        return $user->id === $cronJob->user_id;
    }

    public function delete(User $user, CronJob $cronJob): bool
    {
        return $user->id === $cronJob->user_id;
    }
}
