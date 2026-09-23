<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Console\Command;

class CollectServerMetrics extends Command
{
    protected $signature = 'portway:collect-server-metrics';

    protected $description = 'Record a ServerMetric snapshot for every online hosting node.';

    public function handle(ProvisionerDriver $driver): int
    {
        Server::query()->where('status', '!=', 'offline')->each(function (Server $server) use ($driver) {
            try {
                $metrics = $driver->collectServerMetrics($server);

                $server->metrics()->create([...$metrics, 'recorded_at' => now()]);

                $server->update([
                    'cpu_load' => $metrics['cpu_percent'],
                    'memory_used_percent' => $metrics['memory_percent'],
                    'last_heartbeat_at' => now(),
                    'status' => 'online',
                ]);
            } catch (\Throwable $e) {
                $server->update(['status' => 'degraded']);
                report($e);
            }
        });

        return self::SUCCESS;
    }
}
