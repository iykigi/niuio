<?php

namespace App\Services\Dns;

use App\Enums\DomainStatus;
use App\Events\DomainConnected;
use App\Exceptions\QuotaExceededException;
use App\Jobs\IssueSslCertificateJob;
use App\Models\Domain;
use App\Models\Site;
use App\Models\User;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Validation\ValidationException;

class DomainConnectionService
{
    public function __construct(private ProvisionerDriver $driver)
    {
    }

    public function addDomain(User $user, Site $site, string $hostname, string $type = 'primary'): Domain
    {
        $hostname = strtolower(trim($hostname, ". \t\n\r\0\x0B"));

        if ($user->domains()->count() >= $user->max_domains) {
            throw QuotaExceededException::domainLimit($user->max_domains);
        }

        if (Domain::where('hostname', $hostname)->exists()) {
            // Never let one account silently take over a hostname another
            // account already has attached — even one they no longer use.
            throw ValidationException::withMessages([
                'hostname' => 'This domain is already connected to a Portway account. If you own it, contact support to transfer it.',
            ]);
        }

        $domain = Domain::create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'hostname' => $hostname,
            'type' => $type,
            'status' => DomainStatus::PendingDns,
        ]);

        $this->checkDns($domain);

        return $domain;
    }

    /**
     * Expected DNS records the user must publish at their registrar,
     * shown verbatim in the "Add Domain" wizard.
     */
    public function expectedRecords(Domain $domain): array
    {
        $serverIp = config('portway.server_ip');
        $records = [
            ['type' => 'A', 'name' => '@', 'value' => $serverIp],
        ];

        if (! str_starts_with($domain->hostname, 'www.')) {
            $records[] = ['type' => 'CNAME', 'name' => 'www', 'value' => $domain->hostname];
        }

        return $records;
    }

    public function checkDns(Domain $domain): Domain
    {
        $result = $this->driver->checkDnsPropagation($domain);

        $domain->update([
            'last_dns_check_at' => now(),
            'last_dns_check_result' => $result,
        ]);

        if ($result['matches_expected'] ?? false) {
            // Only pending/partially-detected domains are "newly" connected:
            // one already connected, issuing SSL or active must not be
            // knocked back to Connected or have its certificate re-issued
            // every time DNS is re-checked.
            $wasConnectedAlready = ! in_array($domain->status, [DomainStatus::PendingDns, DomainStatus::DnsDetected, DomainStatus::Failed], true);

            if ($wasConnectedAlready) {
                return $domain->fresh();
            }

            $domain->update(['status' => DomainStatus::Connected, 'verified_at' => $domain->verified_at ?? now()]);

            DomainConnected::dispatch($domain->fresh());
            IssueSslCertificateJob::dispatch($domain->fresh())->onQueue(config('queue.names.ssl'));
        } elseif ($domain->status === DomainStatus::PendingDns && (! empty($result['a']) || ! empty($result['cname']))) {
            $domain->update(['status' => DomainStatus::DnsDetected]);
        }

        return $domain->fresh();
    }
}
