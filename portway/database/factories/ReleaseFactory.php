<?php

namespace Database\Factories;

use App\Enums\ReleasePlatform;
use App\Enums\ReleaseStatus;
use App\Models\Application;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Release>
 */
class ReleaseFactory extends Factory
{
    protected $model = Release::class;

    public function definition(): array
    {
        $platform = fake()->randomElement(ReleasePlatform::cases());
        $version = fake()->numberBetween(1, 9).'.'.fake()->numberBetween(0, 9).'.'.fake()->numberBetween(0, 9);

        return [
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'platform' => $platform,
            'version' => $version,
            'status' => ReleaseStatus::Draft,
            'disk' => 'releases',
            'path' => 'testing/'.fake()->uuid().'.'.$platform->allowedExtensions()[0],
            'original_filename' => 'installer.'.$platform->allowedExtensions()[0],
            'size_bytes' => fake()->numberBetween(1048576, 104857600),
            'checksum_sha256' => hash('sha256', fake()->uuid()),
            'architecture' => 'x64',
            'minimum_os' => null,
            'changelog' => fake()->sentence(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Release $release) {
            if (! $release->user_id && $release->application_id) {
                $release->user_id = Application::find($release->application_id)?->user_id;
            }
        });
    }

    public function forPlatform(ReleasePlatform $platform): static
    {
        return $this->state(fn () => [
            'platform' => $platform,
            'path' => 'testing/'.fake()->uuid().'.'.$platform->allowedExtensions()[0],
            'original_filename' => 'installer.'.$platform->allowedExtensions()[0],
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ReleaseStatus::Active,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => ReleaseStatus::Archived,
            'published_at' => now()->subMonth(),
            'archived_at' => now()->subDay(),
        ]);
    }

    public function trashed(): static
    {
        return $this->state(fn () => [
            'status' => ReleaseStatus::Trashed,
            'archived_at' => now()->subDay(),
            'trashed_at' => now(),
        ]);
    }
}
