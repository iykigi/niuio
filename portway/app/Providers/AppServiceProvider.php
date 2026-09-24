<?php

namespace App\Providers;

use App\Services\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        // Outside production, writing an attribute that isn't $fillable
        // throws instead of being silently dropped — that exact bug used
        // to make suspensions, backups and SSL errors quietly not save.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Super Admins bypass every other Gate/Policy check platform-wide.
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
