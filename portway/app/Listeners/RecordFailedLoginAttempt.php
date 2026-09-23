<?php

namespace App\Listeners;

use App\Models\LoginAttempt;
use Illuminate\Auth\Events\Failed;

class RecordFailedLoginAttempt
{
    public function handle(Failed $event): void
    {
        LoginAttempt::create([
            'user_id' => $event->user?->id,
            'email' => $event->credentials['email'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'successful' => false,
            'failure_reason' => 'invalid_credentials',
        ]);
    }
}
