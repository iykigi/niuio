<?php

namespace Database\Factories;

use App\Enums\SiteStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->domainWord().'-site';

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'project_type' => 'php_empty',
            'runtime' => 'php',
            'document_root' => 'public',
            'php_version' => '8.3',
            'status' => SiteStatus::Active,
            'disk_usage_bytes' => 0,
        ];
    }

    public function provisioning(): static
    {
        return $this->state(fn (array $attributes) => ['status' => SiteStatus::Provisioning]);
    }

    public function node(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_type' => 'nodejs',
            'runtime' => 'node',
            'php_version' => null,
            'node_version' => '20',
        ]);
    }
}
