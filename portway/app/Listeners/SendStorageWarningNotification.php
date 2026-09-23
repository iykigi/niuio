<?php

namespace App\Listeners;

use App\Events\StorageQuotaThresholdReached;
use App\Notifications\HostingActivityNotification;

class SendStorageWarningNotification
{
    public function handle(StorageQuotaThresholdReached $event): void
    {
        $level = $event->percentUsed >= 100 ? 'danger' : ($event->percentUsed >= 90 ? 'warning' : 'info');
        $title = $event->percentUsed >= 100 ? 'Storage full' : "Storage {$event->percentUsed}% full";
        $body = $event->percentUsed >= 100
            ? 'Your account has reached its storage quota. New uploads and deployments are blocked until you free up space.'
            : "You've used {$event->percentUsed}% of your storage quota. Consider removing unused files or backups.";

        $event->user->notify(new HostingActivityNotification($title, $body, $level, '/usage', alsoEmail: $event->percentUsed >= 95));
    }
}
