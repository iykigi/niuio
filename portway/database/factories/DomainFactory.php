<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'hostname' => fake()->unique()->domainName(),
            'type' => 'primary',
            'status' => 'pending_dns',
            'verification_token' => Str::random(32),
            'force_https' => true,
        ];
    }

    public function configure(): static
    {
        // A domain always belongs to the same account as the site it's
        // attached to — keep user_id in sync even when the factory
        // generates its own Site rather than being handed one via for().
        return $this->afterMaking(function (Domain $domain) {
            if (! $domain->user_id) {
                $domain->user_id = Site::find($domain->site_id)?->user_id;
            }
        })->afterCreating(function (Domain $domain) {
            if (! $domain->user_id) {
                $domain->update(['user_id' => $domain->site->user_id]);
            }
        });
    }

    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'verified_at' => now(),
        ]);
    }
}
