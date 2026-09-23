<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'storage_quota_mb' => config('portway.defaults.storage_quota_mb'),
            'bandwidth_quota_mb' => config('portway.defaults.bandwidth_quota_mb'),
            'max_websites' => config('portway.defaults.max_websites'),
            'max_databases' => config('portway.defaults.max_databases'),
            'max_domains' => config('portway.defaults.max_domains'),
            'max_cron_jobs' => config('portway.defaults.max_cron_jobs'),
            'max_backups' => config('portway.defaults.max_backups'),
            'max_email_accounts' => config('portway.defaults.max_email_accounts'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    public function suspended(string $reason = 'Terms of service violation'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_suspended' => true,
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);
    }
}
