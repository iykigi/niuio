<?php

namespace App\Providers;

use App\Events\BackupCompleted;
use App\Events\DomainConnected;
use App\Events\DeploymentFinished;
use App\Events\SiteProvisioned;
use App\Events\SslIssued;
use App\Events\StorageQuotaThresholdReached;
use App\Listeners\LogHostingActivity;
use App\Listeners\NotifyDeploymentFinished;
use App\Listeners\SendHostingNotification;
use App\Listeners\SendStorageWarningNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed as LoginFailed;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SiteProvisioned::class => [SendHostingNotification::class, LogHostingActivity::class],
        DomainConnected::class => [SendHostingNotification::class, LogHostingActivity::class],
        SslIssued::class => [SendHostingNotification::class, LogHostingActivity::class],
        BackupCompleted::class => [SendHostingNotification::class, LogHostingActivity::class],
        StorageQuotaThresholdReached::class => [SendStorageWarningNotification::class],
        DeploymentFinished::class => [NotifyDeploymentFinished::class],
        Login::class => [\App\Listeners\RecordLoginAttempt::class],
        LoginFailed::class => [\App\Listeners\RecordFailedLoginAttempt::class],
    ];

    public function boot(): void
    {
        //
    }
}
