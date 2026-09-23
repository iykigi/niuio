<?php

namespace App\Listeners;

use App\Events\BackupCompleted;
use App\Events\DomainConnected;
use App\Events\SiteProvisioned;
use App\Events\SslIssued;
use App\Services\Security\ActivityLogger;

class LogHostingActivity
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public function handle(SiteProvisioned|DomainConnected|SslIssued|BackupCompleted $event): void
    {
        match (true) {
            $event instanceof SiteProvisioned => $this->logger->log(
                action: 'site.provisioned',
                target: $event->site,
                description: "Website \"{$event->site->name}\" finished provisioning.",
                userId: $event->site->user_id,
            ),
            $event instanceof DomainConnected => $this->logger->log(
                action: 'domain.connected',
                target: $event->domain,
                description: "Domain {$event->domain->hostname} connected.",
                userId: $event->domain->user_id,
            ),
            $event instanceof SslIssued => $this->logger->log(
                action: 'ssl.issued',
                target: $event->certificate,
                description: "SSL certificate issued for {$event->certificate->domain->hostname}.",
                userId: $event->certificate->domain->user_id,
            ),
            $event instanceof BackupCompleted => $this->logger->log(
                action: 'backup.completed',
                target: $event->backup,
                description: "Backup #{$event->backup->id} completed for site #{$event->backup->site_id}.",
                userId: $event->backup->user_id,
            ),
        };
    }
}
