<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'locale',
        'storage_quota_mb',
        'bandwidth_quota_mb',
        'max_websites',
        'max_databases',
        'max_domains',
        'max_cron_jobs',
        'max_backups',
        'max_email_accounts',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Account limit columns and the config('portway.defaults.*') value a
     * new account starts with — read at signup time, so a change made in
     * Admin > Settings applies to every account created afterwards.
     */
    public const DEFAULT_LIMIT_COLUMNS = [
        'storage_quota_mb', 'bandwidth_quota_mb', 'max_websites', 'max_databases',
        'max_domains', 'max_cron_jobs', 'max_backups', 'max_email_accounts',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            foreach (self::DEFAULT_LIMIT_COLUMNS as $column) {
                if ($user->getAttribute($column) === null && config("portway.defaults.{$column}") !== null) {
                    $user->setAttribute($column, (int) config("portway.defaults.{$column}"));
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    // --- Relationships --------------------------------------------------

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    // --- Two factor helpers ----------------------------------------------

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    // --- Quota helpers ----------------------------------------------------

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['Super Admin', 'Admin', 'Support', 'Moderator']);
    }
}
