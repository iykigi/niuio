<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'server_id', 'name', 'slug', 'project_type', 'runtime',
        'document_root', 'php_version', 'node_version',
        'node_install_command', 'node_build_command', 'node_start_command', 'node_port',
        'php_memory_limit_mb', 'php_upload_max_mb', 'php_max_execution_seconds', 'php_extensions',
        'status', 'status_message', 'provisioning_progress', 'force_https',
        'git_repository_id',
        'node_process_status',
        'disk_usage_bytes',
        'bandwidth_used_mb',
        'disk_usage_calculated_at',
        'last_deployed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'php_extensions' => 'array',
            'force_https' => 'boolean',
            'disk_usage_calculated_at' => 'datetime',
            'last_deployed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Site $site) {
            $site->slug ??= static::generateUniqueSlug($site->name);
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'site';
        $slug = $base.'-'.Str::lower(Str::random(6));

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }

    // --- Relationships ----------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function primaryDomain()
    {
        return $this->hasOne(Domain::class)->where('type', 'primary');
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function gitRepository(): HasOne
    {
        return $this->hasOne(GitRepository::class);
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    public function environmentVariables(): HasMany
    {
        return $this->hasMany(EnvironmentVariable::class);
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(Redirect::class);
    }

    public function resourceUsage(): HasMany
    {
        return $this->hasMany(ResourceUsage::class);
    }

    // --- Path & display helpers --------------------------------------------

    /**
     * The site's isolated root directory, relative to the "hosting" disk:
     * {account_id}/{slug}/. Every File Manager, deployment and backup
     * operation is scoped underneath this and must never be able to
     * escape it (see App\Services\Files\PathResolver).
     */
    public function rootPath(): string
    {
        return "{$this->user_id}/{$this->slug}";
    }

    public function documentRootPath(): string
    {
        return trim($this->rootPath().'/'.trim($this->document_root, '/'), '/');
    }

    public function temporaryHostname(): string
    {
        return "{$this->slug}.".config('portway.temporary_domain_suffix');
    }

    public function diskUsageHuman(): string
    {
        return number_format($this->disk_usage_bytes / 1048576, 1).' MB';
    }

    public function isActive(): bool
    {
        return $this->status === SiteStatus::Active;
    }
}
