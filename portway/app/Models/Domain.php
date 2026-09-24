<?php

namespace App\Models;

use App\Enums\DomainStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Domain extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'site_id', 'user_id', 'hostname', 'type', 'document_root_override',
        'status', 'verification_token', 'force_https',
        'verified_at',
        'last_dns_check_at',
        'last_dns_check_result',
    ];

    protected function casts(): array
    {
        return [
            'status' => DomainStatus::class,
            'verified_at' => 'datetime',
            'last_dns_check_at' => 'datetime',
            'last_dns_check_result' => 'array',
            'force_https' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Domain $domain) {
            $domain->verification_token ??= Str::random(48);
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function sslCertificate(): HasOne
    {
        return $this->hasOne(SslCertificate::class)->latestOfMany();
    }

    public function emailAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function isRootDomain(): bool
    {
        return substr_count($this->hostname, '.') === 1
            || in_array($this->type, ['primary', 'temporary']);
    }

    public function isTemporary(): bool
    {
        return $this->type === 'temporary';
    }
}
