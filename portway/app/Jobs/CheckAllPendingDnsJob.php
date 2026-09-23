<?php

namespace App\Jobs;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Services\Dns\DomainConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs every few minutes (see routes/console.php) so a domain someone
 * pointed at Portway five minutes ago becomes "Connected" without them
 * needing to click a manual "Check DNS" button — though that button
 * (DomainConnectionService::checkDns) exists too, for impatience.
 */
class CheckAllPendingDnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DomainConnectionService $service): void
    {
        Domain::query()
            ->whereIn('status', [DomainStatus::PendingDns, DomainStatus::DnsDetected])
            ->whereNotIn('type', ['temporary'])
            ->each(fn (Domain $domain) => $service->checkDns($domain));
    }
}
