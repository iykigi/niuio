<?php

namespace App\Events;

use App\Models\SslCertificate;
use Illuminate\Foundation\Events\Dispatchable;

class SslIssued
{
    use Dispatchable;

    public function __construct(public SslCertificate $certificate)
    {
    }
}
