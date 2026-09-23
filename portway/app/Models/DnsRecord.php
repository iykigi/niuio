<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id', 'type', 'name', 'content', 'ttl', 'priority', 'weight',
        'port', 'is_managed_by_portway', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_managed_by_portway' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function displayContent(): string
    {
        return match ($this->type) {
            'MX' => "{$this->priority} {$this->content}",
            'SRV' => "{$this->priority} {$this->weight} {$this->port} {$this->content}",
            default => $this->content,
        };
    }
}
