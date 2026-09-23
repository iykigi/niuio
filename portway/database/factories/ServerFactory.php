<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->domainWord().'-web-01',
            'hostname' => fake()->unique()->domainName(),
            'ip_address' => fake()->ipv4(),
            'region' => fake()->randomElement(['eu-central', 'us-east', 'ap-south']),
            'role' => 'combined',
            'status' => 'online',
            'web_server' => 'nginx',
            'max_sites' => 500,
            'current_sites' => 0,
            'ssh_user' => 'portway',
            'ssh_port' => 22,
            'is_control_plane' => false,
        ];
    }
}
