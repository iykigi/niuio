<?php

namespace App\Console\Commands;

use App\Services\Ssl\SslService;
use Illuminate\Console\Command;

class RenewSslCertificates extends Command
{
    protected $signature = 'portway:renew-ssl';

    protected $description = 'Renew every SSL certificate due within 30 days (auto-renew enabled).';

    public function handle(SslService $sslService): int
    {
        $due = $sslService->certificatesDueForRenewal();
        $this->info("Found {$due->count()} certificate(s) due for renewal.");

        foreach ($due as $certificate) {
            try {
                $sslService->renew($certificate);
                $this->line("Renewed: {$certificate->domain->hostname}");
            } catch (\Throwable $e) {
                $this->error("Failed to renew {$certificate->domain->hostname}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
