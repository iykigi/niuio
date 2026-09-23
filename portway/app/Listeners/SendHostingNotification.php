<?php

namespace App\Listeners;

use App\Events\BackupCompleted;
use App\Events\DomainConnected;
use App\Events\SiteProvisioned;
use App\Events\SslIssued;
use App\Notifications\HostingActivityNotification;

class SendHostingNotification
{
    public function handle(SiteProvisioned|DomainConnected|SslIssued|BackupCompleted $event): void
    {
        [$user, $notification] = match (true) {
            $event instanceof SiteProvisioned => [
                $event->site->user,
                new HostingActivityNotification(
                    'Website ready',
                    "{$event->site->name} has finished provisioning and is now live.",
                    'success',
                    "/sites/{$event->site->id}",
                ),
            ],
            $event instanceof DomainConnected => [
                $event->domain->user,
                new HostingActivityNotification(
                    'Domain connected',
                    "{$event->domain->hostname} is now pointed at Portway and connected.",
                    'success',
                    "/domains/{$event->domain->id}",
                ),
            ],
            $event instanceof SslIssued => [
                $event->certificate->domain->user,
                new HostingActivityNotification(
                    'SSL installed',
                    "A free SSL certificate is now active for {$event->certificate->domain->hostname}.",
                    'success',
                    "/domains/{$event->certificate->domain->id}",
                ),
            ],
            $event instanceof BackupCompleted => [
                $event->backup->user,
                new HostingActivityNotification(
                    'Backup completed',
                    "A {$event->backup->type} backup of {$event->backup->site->name} finished successfully.",
                    'success',
                    "/sites/{$event->backup->site_id}/backups",
                ),
            ],
        };

        $user->notify($notification);
    }
}
