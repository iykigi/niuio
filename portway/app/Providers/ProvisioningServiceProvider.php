<?php

namespace App\Providers;

use App\Services\Provisioning\Drivers\LocalProvisionerDriver;
use App\Services\Provisioning\Drivers\SshProvisionerDriver;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Support\ServiceProvider;

class ProvisioningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProvisionerDriver::class, function () {
            return match (config('portway.provisioner')) {
                'ssh' => new SshProvisionerDriver,
                default => new LocalProvisionerDriver,
            };
        });
    }
}
