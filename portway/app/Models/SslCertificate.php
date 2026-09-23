<?php

namespace App\Models;

use App\Enums\SslStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id', 'provider', 'status', 'issuer', 'certificate', 'private_key',
        'chain', 'issued_at', 'expires_at', 'auto_renew',
    ];

    protected $hidden = ['private_key'];

    protected function casts(): array
    {
        return [
            'status' => SslStatus::class,
            'private_key' => 'encrypted',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_renewal_attempt_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function daysUntilExpiry(): ?int
    {
        return $this->expires_at ? now()->diffInDays($this->expires_at, false) : null;
    }

    public function needsRenewal(): bool
    {
        return $this->auto_renew
            && $this->status === SslStatus::Active
            && $this->daysUntilExpiry() !== null
            && $this->daysUntilExpiry() <= 30;
    }
}
