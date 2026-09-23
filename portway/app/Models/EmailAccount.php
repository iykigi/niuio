<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id', 'user_id', 'local_part', 'address', 'password', 'quota_mb',
        'forward_to', 'autoresponder_enabled', 'autoresponder_subject',
        'autoresponder_body', 'status',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'autoresponder_enabled' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usedPercent(): float
    {
        $quotaBytes = $this->quota_mb * 1048576;

        return $quotaBytes > 0 ? round(($this->used_bytes / $quotaBytes) * 100, 1) : 0.0;
    }
}
