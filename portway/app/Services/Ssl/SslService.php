<?php

namespace App\Services\Ssl;

use App\Enums\SslStatus;
use App\Events\SslIssued;
use App\Models\Domain;
use App\Models\SslCertificate;
use App\Services\Provisioning\ProvisionerDriver;
use Throwable;

class SslService
{
    public function __construct(private ProvisionerDriver $driver)
    {
    }

    public function issueForDomain(Domain $domain, bool $silent = false): SslCertificate
    {
        $certificate = $domain->sslCertificate ?? SslCertificate::create([
            'domain_id' => $domain->id,
            'status' => SslStatus::Pending,
        ]);

        $certificate->update(['status' => SslStatus::Issuing]);
        $domain->update(['status' => 'ssl_installing']);

        try {
            $this->driver->issueSslCertificate($certificate);
            $certificate->update(['status' => SslStatus::Active]);
            $domain->update(['status' => 'active']);

            if (! $silent) {
                SslIssued::dispatch($certificate->fresh());
            }
        } catch (Throwable $e) {
            $certificate->update(['status' => SslStatus::Failed, 'last_error' => $e->getMessage()]);
            $domain->update(['status' => 'failed']);
            throw $e;
        }

        return $certificate->fresh();
    }

    public function revoke(SslCertificate $certificate): void
    {
        $this->driver->revokeSslCertificate($certificate);
        $certificate->update(['status' => SslStatus::Revoked, 'auto_renew' => false]);
    }

    /**
     * Certificates within 30 days of expiry with auto-renew on — run
     * daily by App\Console\Commands\RenewSslCertificates.
     */
    public function certificatesDueForRenewal()
    {
        return SslCertificate::query()
            ->where('status', SslStatus::Active)
            ->where('auto_renew', true)
            ->where('expires_at', '<=', now()->addDays(30))
            ->get();
    }

    public function renew(SslCertificate $certificate): void
    {
        $certificate->update(['status' => SslStatus::Renewing, 'last_renewal_attempt_at' => now()]);

        try {
            $this->driver->issueSslCertificate($certificate);
            $certificate->update(['status' => SslStatus::Active]);
        } catch (Throwable $e) {
            $certificate->update(['status' => SslStatus::Failed, 'last_error' => $e->getMessage()]);
            throw $e;
        }
    }
}
