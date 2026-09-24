<?php

use App\Models\Site;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists only the token owner\'s sites', function () {
    $user = User::factory()->create();
    Site::factory()->create(['user_id' => $user->id]);
    Site::factory()->create(['user_id' => User::factory()->create()->id]);

    Sanctum::actingAs($user, ['sites:read']);

    // Also proves the "api" rate limiter used by the api middleware group
    // is defined — without it every API route fails with a 500.
    $this->getJson('/api/v1/sites')
        ->assertOk()
        ->assertHeader('X-RateLimit-Limit', 60)
        ->assertJsonCount(1, 'data');
});

it('rejects API requests without a token', function () {
    $this->getJson('/api/v1/sites')->assertUnauthorized();
});
