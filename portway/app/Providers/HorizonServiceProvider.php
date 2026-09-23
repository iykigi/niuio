<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Horizon::routeSmsNotificationsTo(null);
        Horizon::routeMailNotificationsTo(config('app.brand.support_email'));
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return $user->hasAnyRole(['Super Admin', 'Admin']);
        });
    }
}
