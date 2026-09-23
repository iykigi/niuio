<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'tagline' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'website_url' => 'https://'.fake()->domainName(),
            'support_email' => fake()->safeEmail(),
            'is_listed' => true,
        ];
    }

    public function unlisted(): static
    {
        return $this->state(fn () => ['is_listed' => false]);
    }
}
