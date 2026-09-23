<?php

use App\Enums\ReleasePlatform;
use App\Enums\ReleaseStatus;
use App\Models\Application;
use App\Models\Release;
use App\Models\User;
use App\Services\Releases\ReleaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    seedRolesAndPermissions();
    Storage::fake('releases');
});

function uploadedBuild(string $name = 'setup.exe', int $kilobytes = 64): UploadedFile
{
    return UploadedFile::fake()->create($name, $kilobytes, 'application/octet-stream');
}

it('stores an uploaded build as a draft rather than publishing it', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    $release = app(ReleaseService::class)->createDraft(
        $user,
        $application,
        ReleasePlatform::Windows,
        uploadedBuild(),
        ['version' => '1.0.0', 'changelog' => 'First public build.'],
    );

    expect($release->status)->toBe(ReleaseStatus::Draft)
        ->and($release->published_at)->toBeNull()
        ->and($release->checksum_sha256)->toHaveLength(64)
        ->and($application->hasAnyLiveRelease())->toBeFalse();

    Storage::disk('releases')->assertExists($release->path);
});

it('rejects a build whose file type does not belong to the platform', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    app(ReleaseService::class)->createDraft(
        $user,
        $application,
        ReleasePlatform::MacOS,
        uploadedBuild('installer.exe'),
        ['version' => '1.0.0', 'changelog' => 'Nope.'],
    );
})->throws(RuntimeException::class);

it('will not publish a build that is missing its release notes', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);
    $release = Release::factory()->for($application)->create([
        'user_id' => $user->id,
        'changelog' => null,
    ]);

    expect($release->isReadyToPublish())->toBeFalse();

    app(ReleaseService::class)->publish($release, $user);
})->throws(RuntimeException::class);

it('keeps exactly one live build per platform and archives the one it replaces', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    $old = Release::factory()->for($application)->forPlatform(ReleasePlatform::Windows)->active()->create([
        'user_id' => $user->id,
        'version' => '1.0.0',
    ]);
    Storage::disk('releases')->put($old->path, 'old');

    $new = Release::factory()->for($application)->forPlatform(ReleasePlatform::Windows)->create([
        'user_id' => $user->id,
        'version' => '2.0.0',
    ]);
    Storage::disk('releases')->put($new->path, 'new');

    app(ReleaseService::class)->publish($new, $user);

    expect($new->refresh()->status)->toBe(ReleaseStatus::Active)
        ->and($old->refresh()->status)->toBe(ReleaseStatus::Archived)
        ->and($old->archived_at)->not->toBeNull()
        ->and($application->activeReleases()->count())->toBe(1);
});

it('lets an owner roll back to an archived build, archiving the current one', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    $current = Release::factory()->for($application)->forPlatform(ReleasePlatform::Linux)->active()->create([
        'user_id' => $user->id, 'version' => '3.0.0',
    ]);
    Storage::disk('releases')->put($current->path, 'current');

    $previous = Release::factory()->for($application)->forPlatform(ReleasePlatform::Linux)->archived()->create([
        'user_id' => $user->id, 'version' => '2.9.0',
    ]);
    Storage::disk('releases')->put($previous->path, 'previous');

    app(ReleaseService::class)->publish($previous, $user);

    expect($previous->refresh()->status)->toBe(ReleaseStatus::Active)
        ->and($current->refresh()->status)->toBe(ReleaseStatus::Archived);
});

it('hides a platform entirely once its live build is taken offline', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    $release = Release::factory()->for($application)->forPlatform(ReleasePlatform::MacOS)->active()->create([
        'user_id' => $user->id,
    ]);

    expect($application->liveDownloads())->toHaveCount(1);

    app(ReleaseService::class)->archive($release, $user);

    expect($application->fresh()->liveDownloads())->toHaveCount(0)
        ->and($application->fresh()->isVisibleToPublic())->toBeFalse();
});

it('moves an owner-deleted build to the admin archive instead of destroying it', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);
    $release = Release::factory()->for($application)->active()->create(['user_id' => $user->id]);
    Storage::disk('releases')->put($release->path, 'bytes');

    app(ReleaseService::class)->moveToAdminArchive($release, $user);

    expect($release->refresh()->status)->toBe(ReleaseStatus::Trashed)
        ->and($release->trashed_by)->toBe($user->id);

    // The file survives: an administrator can still restore it.
    Storage::disk('releases')->assertExists($release->path);
});

it('only lets staff restore or purge a build from the admin archive', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $application = Application::factory()->create(['user_id' => $owner->id]);
    $release = Release::factory()->for($application)->trashed()->create(['user_id' => $owner->id]);
    Storage::disk('releases')->put($release->path, 'bytes');

    expect($owner->can('restore', $release))->toBeFalse()
        ->and($owner->can('view', $release))->toBeFalse()
        ->and($admin->can('restore', $release))->toBeTrue();

    app(ReleaseService::class)->restoreFromAdminArchive($release, $admin);

    expect($release->refresh()->status)->toBe(ReleaseStatus::Archived)
        ->and($release->trashed_at)->toBeNull();
});

it('destroys the file when an administrator purges a build', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $application = Application::factory()->create(['user_id' => $owner->id]);
    $release = Release::factory()->for($application)->trashed()->create(['user_id' => $owner->id]);
    Storage::disk('releases')->put($release->path, 'bytes');
    $path = $release->path;

    app(ReleaseService::class)->purge($release, $admin);

    Storage::disk('releases')->assertMissing($path);
    expect(Release::find($release->id))->toBeNull();
});

it('refuses to purge a build that is not in the admin archive', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $release = Release::factory()->active()->create();

    app(ReleaseService::class)->purge($release, $admin);
})->throws(RuntimeException::class);
