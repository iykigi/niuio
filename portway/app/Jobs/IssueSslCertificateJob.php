<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\Ssl\SslService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class IssueSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Domain $domain)
    {
    }

    public function handle(SslService $sslService): void
    {
        try {
            $sslService->issueForDomain($this->domain);
        } catch (Throwable $e) {
            report($e);
            throw $e;
        }
    }
}
