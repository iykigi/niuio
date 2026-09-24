<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('portway.super_admin.email');
        $password = config('portway.super_admin.password');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Portway Super Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }

        $this->command?->warn(
            "Seeded Super Admin: {$email} — set PORTWAY_SUPER_ADMIN_PASSWORD before deploying to production."
        );
    }
}
