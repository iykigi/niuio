<?php

namespace App\Events;

use App\Models\Domain;
use Illuminate\Foundation\Events\Dispatchable;

class DomainConnected
{
    use Dispatchable;

    public function __construct(public Domain $domain)
    {
    }
}
