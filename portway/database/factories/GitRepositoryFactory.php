<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GitRepository>
 */
class GitRepositoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'provider' => 'github',
            'url' => 'https://github.com/example/'.fake()->unique()->slug(2).'.git',
            'branch' => 'main',
            'auto_deploy_on_push' => false,
        ];
    }
}
