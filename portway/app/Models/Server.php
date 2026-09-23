<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name', 'hostname', 'ip_address', 'internal_ip', 'region', 'role',
        'status', 'web_server', 'installed_php_versions', 'installed_node_versions',
        'max_sites', 'ssh_port', 'ssh_user', 'ssh_private_key', 'is_control_plane',
    ];

    protected $hidden = ['ssh_private_key'];

    protected function casts(): array
    {
        return [
            'installed_php_versions' => 'array',
            'installed_node_versions' => 'array',
            'ssh_private_key' => 'encrypted',
            'is_control_plane' => 'boolean',
            'last_heartbeat_at' => 'datetime',
            'cpu_load' => 'float',
            'memory_used_percent' => 'float',
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    public function latestMetric()
    {
        return $this->hasOne(ServerMetric::class)->latestOfMany('recorded_at');
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function hasCapacity(): bool
    {
        return $this->current_sites < $this->max_sites;
    }

    /**
     * Pick the best available node for a new site: online, has capacity,
     * least loaded first. Used by SiteProvisioningService.
     */
    public static function pickForNewSite(): ?self
    {
        return static::query()
            ->where('status', 'online')
            ->where('role', '!=', 'database')
            ->whereColumn('current_sites', '<', 'max_sites')
            ->orderBy('cpu_load')
            ->first();
    }
}
