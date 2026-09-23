<?php

namespace App\Listeners;

use App\Models\LoginAttempt;
use Illuminate\Auth\Events\Login;

class RecordLoginAttempt
{
    public function handle(Login $event): void
    {
        LoginAttempt::create([
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'successful' => true,
        ]);

        $event->user->forceFill([
            'last_seen_at' => now(),
            'last_seen_ip' => request()->ip(),
        ])->save();
    }
}
