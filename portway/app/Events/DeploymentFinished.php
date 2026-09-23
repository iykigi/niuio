<?php

namespace App\Events;

use App\Models\Deployment;
use Illuminate\Foundation\Events\Dispatchable;

class DeploymentFinished
{
    use Dispatchable;

    public function __construct(public Deployment $deployment)
    {
    }
}
