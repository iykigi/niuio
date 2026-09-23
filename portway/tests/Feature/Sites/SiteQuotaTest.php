<?php

use App\Exceptions\QuotaExceededException;
use App\Jobs\ProvisionSiteJob;
use App\Models\Site;
use App\Models\User;
use App\Services\Provisioning\SiteProvisioningService;
use Illuminate\Support\Facades\Queue;

it('creates a website and queues its provisioning job when the account has room', function () {
    Queue::fake();

    $user = User::factory()->create(['max_websites' => 3]);

    $site = app(SiteProvisioningService::class)->create($user, [
        'name' => 'My Blog',
        'project_type' => 'php_empty',
    ]);

    expect($site->user_id)->toBe($user->id);
    expect($site->status->value)->toBe('provisioning');

    Queue::assertPushed(ProvisionSiteJob::class, fn ($job) => $job->site->is($site));
});

it('refuses to create a website once the account has reached its website limit', function () {
    Queue::fake();

    $user = User::factory()->create(['max_websites' => 2]);
    Site::factory()->count(2)->create(['user_id' => $user->id]);

    expect(fn () => app(SiteProvisioningService::class)->create($user, [
        'name' => 'One Too Many',
        'project_type' => 'php_empty',
    ]))->toThrow(QuotaExceededException::class);

    expect($user->sites()->count())->toBe(2);
    Queue::assertNotPushed(ProvisionSiteJob::class);
});

it('refuses to create a website once the account is over its storage quota', function () {
    Queue::fake();

    $user = User::factory()->create([
        'max_websites' => 10,
        'storage_quota_mb' => 1,
    ]);

    Site::factory()->create([
        'user_id' => $user->id,
        'disk_usage_bytes' => 5 * 1048576, // 5 MB used against a 1 MB quota
    ]);

    expect(fn () => app(SiteProvisioningService::class)->create($user, [
        'name' => 'No Room Left',
        'project_type' => 'php_empty',
    ]))->toThrow(QuotaExceededException::class);
});

it('gives every new account the platform default free quota with no payment required', function () {
    $user = User::factory()->create();

    expect($user->storage_quota_mb)->toBe(config('portway.defaults.storage_quota_mb'));
    expect($user->max_websites)->toBe(config('portway.defaults.max_websites'));
});
