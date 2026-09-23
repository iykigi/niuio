<?php

use App\Enums\BackupStatus;
use App\Exceptions\QuotaExceededException;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use App\Models\Site;
use App\Models\User;
use App\Services\Backups\BackupService;
use Illuminate\Support\Facades\Queue;

it('queues a backup for a site when the account has room', function () {
    Queue::fake();

    $user = User::factory()->create(['max_backups' => 3]);
    $site = Site::factory()->create(['user_id' => $user->id]);

    $backup = app(BackupService::class)->create($user, $site);

    expect($backup->status)->toBe(BackupStatus::Queued);
    Queue::assertPushed(CreateBackupJob::class, fn ($job) => $job->backup->is($backup));
});

it('refuses to queue a backup once the site has reached its backup limit', function () {
    Queue::fake();

    $user = User::factory()->create(['max_backups' => 2]);
    $site = Site::factory()->create(['user_id' => $user->id]);

    Backup::factory()->count(2)->create([
        'site_id' => $site->id,
        'user_id' => $user->id,
        'status' => BackupStatus::Completed,
    ]);

    expect(fn () => app(BackupService::class)->create($user, $site))
        ->toThrow(QuotaExceededException::class);

    Queue::assertNotPushed(CreateBackupJob::class);
});

it('does not count failed backups against the backup limit', function () {
    Queue::fake();

    $user = User::factory()->create(['max_backups' => 1]);
    $site = Site::factory()->create(['user_id' => $user->id]);

    Backup::factory()->create([
        'site_id' => $site->id,
        'user_id' => $user->id,
        'status' => BackupStatus::Failed,
    ]);

    $backup = app(BackupService::class)->create($user, $site);

    expect($backup->status)->toBe(BackupStatus::Queued);
});

it('only lets the owner (or staff with backups.manage) restore or delete a backup', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);
    $backup = Backup::factory()->create(['site_id' => $site->id, 'user_id' => $owner->id]);

    expect($owner->can('restore', $backup))->toBeTrue();
    expect($owner->can('delete', $backup))->toBeTrue();
    expect($stranger->can('restore', $backup))->toBeFalse();
    expect($stranger->can('delete', $backup))->toBeFalse();
});
