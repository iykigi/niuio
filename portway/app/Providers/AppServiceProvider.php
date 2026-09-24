<?php

namespace App\Providers;

use App\Services\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Admin > Settings keys (system_settings table) and the config value
     * each one overrides at runtime. Anything not saved there keeps the
     * value from config/portway.php / .env.
     */
    private const RUNTIME_SETTINGS = [
        'default_storage_quota_mb' => 'portway.defaults.storage_quota_mb',
        'default_max_websites' => 'portway.defaults.max_websites',
        'default_max_databases' => 'portway.defaults.max_databases',
        'backups_count_toward_quota' => 'portway.quota_categories.backups',
        'available_php_versions' => 'portway.php_versions',
        'available_node_versions' => 'portway.node_versions',
        'registration_open' => 'portway.features.registration_open',
        'email_hosting_enabled' => 'portway.features.email_hosting',
        'malware_scanning_enabled' => 'portway.features.malware_scanning',
        'server_ip' => 'portway.server_ip',
        'temporary_domain_suffix' => 'portway.temporary_domain_suffix',
    ];

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

        $this->applyRuntimeSettings();

        // Used by the api middleware group (bootstrap/app.php → throttleApi()).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Super Admins bypass every other Gate/Policy check platform-wide.
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }

    private function applyRuntimeSettings(): void
    {
        try {
            $overrides = $this->app->make(Settings::class)->all();
        } catch (\Throwable) {
            // Fresh install: the database or system_settings table doesn't
            // exist yet (e.g. while `php artisan migrate` itself is running).
            return;
        }

        foreach (self::RUNTIME_SETTINGS as $key => $configKey) {
            if (isset($overrides[$key]) && $overrides[$key] !== '' && $overrides[$key] !== []) {
                config([$configKey => $overrides[$key]]);
            }
        }
    }
}
