<?php

use App\Enums\ReleasePlatform;
use App\Models\Application;
use App\Models\Release;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    seedRolesAndPermissions();
    Storage::fake('releases');
});

it('shows only platforms that actually have a live build', function () {
    $application = Application::factory()->create();

    Release::factory()->for($application)->forPlatform(ReleasePlatform::Windows)->active()->create([
        'user_id' => $application->user_id,
        'version' => '1.2.0',
    ]);
    // A macOS build exists but is only a draft — it must not appear anywhere.
    Release::factory()->for($application)->forPlatform(ReleasePlatform::MacOS)->create([
        'user_id' => $application->user_id,
        'version' => '1.2.0',
    ]);

    $response = $this->get(route('apps.show', $application));

    $response->assertOk()
        ->assertSee('Windows')
        ->assertDontSee('Download for macOS');

    expect($application->liveDownloads()->keys()->all())->toBe(['windows']);
});

it('404s the download route for a platform with nothing published', function () {
    $application = Application::factory()->create();
    Release::factory()->for($application)->forPlatform(ReleasePlatform::Windows)->active()->create([
        'user_id' => $application->user_id,
    ]);

    $this->get(route('apps.download', ['application' => $application->slug, 'platform' => 'linux']))
        ->assertNotFound();
});

it('404s an application page with no live build at all', function () {
    $application = Application::factory()->create();
    Release::factory()->for($application)->create(['user_id' => $application->user_id]);

    $this->get(route('apps.show', $application))->assertNotFound();
});

it('404s an unlisted application for the public but previews it for its owner', function () {
    $owner = User::factory()->create();
    $application = Application::factory()->unlisted()->create(['user_id' => $owner->id]);
    Release::factory()->for($application)->active()->create(['user_id' => $owner->id]);

    $this->get(route('apps.show', $application))->assertNotFound();

    $this->actingAs($owner)
        ->get(route('apps.show', $application))
        ->assertOk()
        ->assertSee('Preview');
});

it('streams the live build and counts the download', function () {
    $application = Application::factory()->create();
    $release = Release::factory()->for($application)->forPlatform(ReleasePlatform::Windows)->active()->create([
        'user_id' => $application->user_id,
    ]);
    Storage::disk('releases')->put($release->path, 'installer-bytes');

    $this->get(route('apps.download', ['application' => $application->slug, 'platform' => 'windows']))
        ->assertOk()
        ->assertHeader('content-type', 'application/octet-stream');

    expect($release->refresh()->downloads_count)->toBe(1)
        ->and($application->refresh()->downloads_count)->toBe(1);
});

it('never serves a build that sits in the admin archive', function () {
    $owner = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $owner->id]);
    $release = Release::factory()->for($application)->forPlatform(ReleasePlatform::Linux)->trashed()->create([
        'user_id' => $owner->id,
    ]);
    Storage::disk('releases')->put($release->path, 'bytes');

    // Not through the public route…
    $this->get(route('apps.download', ['application' => $application->slug, 'platform' => 'linux']))
        ->assertNotFound();

    // …and not through the owner's own build route either.
    $this->actingAs($owner)
        ->get(route('releases.download', $release))
        ->assertForbidden();
});

it('does not let one user download another account\'s unpublished build', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $owner->id]);
    $release = Release::factory()->for($application)->create(['user_id' => $owner->id]);
    Storage::disk('releases')->put($release->path, 'bytes');

    $this->actingAs($stranger)
        ->get(route('releases.download', $release))
        ->assertForbidden();
});
