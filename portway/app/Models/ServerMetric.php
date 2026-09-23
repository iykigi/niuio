<?php

namespace App\Models;

use App\Enums\ServerHealthStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_id', 'cpu_percent', 'memory_percent', 'disk_percent',
        'load_1m', 'load_5m', 'load_15m', 'network_rx_bytes', 'network_tx_bytes',
        'service_status', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'service_status' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function healthStatus(): ServerHealthStatus
    {
        if ($this->recorded_at?->lt(now()->subMinutes(5))) {
            return ServerHealthStatus::Offline;
        }

        if ($this->cpu_percent >= 90 || $this->memory_percent >= 90 || $this->disk_percent >= 90) {
            return ServerHealthStatus::Critical;
        }

        if ($this->cpu_percent >= 75 || $this->memory_percent >= 75 || $this->disk_percent >= 80) {
            return ServerHealthStatus::Warning;
        }

        return ServerHealthStatus::Healthy;
    }
}
