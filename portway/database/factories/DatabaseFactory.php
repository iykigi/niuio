<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Database>
 */
class DatabaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'pw_'.fake()->unique()->lexify('????????'),
            'engine' => 'mariadb',
            'host' => '127.0.0.1',
            'port' => 3306,
            'size_bytes' => 0,
            'status' => 'active',
        ];
    }
}
