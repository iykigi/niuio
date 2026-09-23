<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeprovisionSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Site $site)
    {
    }

    public function handle(ProvisionerDriver $driver): void
    {
        foreach ($this->site->databases as $database) {
            foreach ($database->databaseUsers as $databaseUser) {
                $driver->deleteDatabaseUser($databaseUser);
            }
            $driver->deleteDatabase($database);
            $database->delete();
        }

        $driver->removeVirtualHost($this->site);
        $driver->deleteSiteDirectory($this->site);

        if ($this->site->server) {
            $this->site->server->decrement('current_sites');
        }

        $this->site->domains->each(fn ($domain) => $domain->delete());
        $this->site->delete();
    }
}
