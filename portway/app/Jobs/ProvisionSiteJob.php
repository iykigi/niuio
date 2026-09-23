<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Events\SiteProvisioned;
use App\Events\SiteProvisioningProgress;
use App\Models\Database as DatabaseModel;
use App\Models\DatabaseUser;
use App\Models\Domain;
use App\Models\Site;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class ProvisionSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(public Site $site, public array $wizardData = [])
    {
    }

    public function handle(ProvisionerDriver $driver): void
    {
        try {
            $this->step('Creating directory…', 10);
            $driver->createSiteDirectory($this->site);

            $this->step('Configuring server…', 30);
            $temporaryDomain = Domain::create([
                'site_id' => $this->site->id,
                'user_id' => $this->site->user_id,
                'hostname' => $this->site->temporaryHostname(),
                'type' => 'temporary',
                'status' => 'active',
                'verified_at' => now(),
            ]);
            $driver->writeVirtualHost($this->site);

            if ($this->site->runtime === 'php') {
                $driver->setPhpVersion($this->site, $this->site->php_version);
            }

            $this->step('Installing runtime…', 50);
            // Framework-specific bootstrapping (composer create-project,
            // WordPress core download, npm scaffolding, ...) is handled
            // by App\Services\Provisioning\Recipes, keyed off project_type.
            app(\App\Services\Provisioning\Recipes::class)->install($this->site, $driver, $this->wizardData);

            if (($this->wizardData['create_database'] ?? in_array($this->site->project_type, ['wordpress', 'laravel'], true))) {
                $this->step('Creating database…', 65);
                $this->provisionDefaultDatabase();
            }

            $this->step('Configuring domain…', 80);
            $driver->reloadWebServer($this->site);

            $this->step('Generating SSL…', 90);
            app(\App\Services\Ssl\SslService::class)->issueForDomain($temporaryDomain, silent: true);

            $this->site->forceFill([
                'status' => SiteStatus::Active,
                'status_message' => null,
                'provisioning_progress' => 100,
            ])->save();

            $this->step('Website ready.', 100);

            SiteProvisioned::dispatch($this->site->fresh());
        } catch (Throwable $e) {
            report($e);

            $this->site->forceFill([
                'status' => SiteStatus::Failed,
                'status_message' => $e->getMessage(),
            ])->save();

            SiteProvisioningProgress::dispatch($this->site->id, 'Failed: '.$e->getMessage(), $this->site->provisioning_progress, failed: true);

            throw $e;
        }
    }

    private function provisionDefaultDatabase(): void
    {
        $name = 'pw_'.$this->site->user_id.'_'.Str::lower(Str::random(8));

        $database = DatabaseModel::create([
            'user_id' => $this->site->user_id,
            'site_id' => $this->site->id,
            'server_id' => $this->site->server_id,
            'name' => $name,
            'status' => 'provisioning',
        ]);

        app(ProvisionerDriver::class)->createDatabase($database);

        $username = 'pw_'.Str::lower(Str::random(10));
        $plainPassword = Str::password(20);

        $databaseUser = DatabaseUser::create([
            'database_id' => $database->id,
            'username' => $username,
            'password' => $plainPassword,
            'privileges' => ['ALL'],
        ]);

        app(ProvisionerDriver::class)->createDatabaseUser($databaseUser, $database);

        $database->update(['status' => 'active']);

        app(ProvisionerDriver::class)->writeEnvFile($this->site, [
            'APP_ENV' => 'production',
            'APP_URL' => 'https://'.$this->site->temporaryHostname(),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $database->host,
            'DB_PORT' => $database->port,
            'DB_DATABASE' => $database->name,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $plainPassword,
        ]);
    }

    private function step(string $label, int $percent): void
    {
        $this->site->forceFill([
            'status_message' => $label,
            'provisioning_progress' => $percent,
        ])->save();

        SiteProvisioningProgress::dispatch($this->site->id, $label, $percent);
    }

    public function failed(Throwable $exception): void
    {
        $this->site->forceFill([
            'status' => SiteStatus::Failed,
            'status_message' => $exception->getMessage(),
        ])->save();
    }
}
