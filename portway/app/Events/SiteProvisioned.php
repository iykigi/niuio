<?php

namespace App\Events;

use App\Models\Site;
use Illuminate\Foundation\Events\Dispatchable;

class SiteProvisioned
{
    use Dispatchable;

    public function __construct(public Site $site)
    {
    }
}
