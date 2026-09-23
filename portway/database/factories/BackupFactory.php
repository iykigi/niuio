<?php

namespace Database\Factories;

use App\Enums\BackupStatus;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Backup>
 */
class BackupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'user_id' => User::factory(),
            'type' => 'full',
            'trigger' => 'manual',
            'status' => BackupStatus::Completed,
            'disk' => 'backups',
            'size_bytes' => fake()->numberBetween(1024, 1048576),
            'expires_at' => now()->addDays(30),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Backup $backup) {
            if (! $backup->user_id) {
                $backup->user_id = \App\Models\Site::find($backup->site_id)?->user_id;
            }
        });
    }
}
