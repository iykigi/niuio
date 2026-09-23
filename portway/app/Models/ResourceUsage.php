<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceUsage extends Model
{
    protected $table = 'resource_usage';

    protected $fillable = [
        'user_id', 'site_id', 'date', 'storage_bytes', 'bandwidth_bytes', 'requests',
        'errors_5xx', 'errors_4xx', 'cpu_seconds', 'avg_memory_mb', 'db_connections_peak',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
