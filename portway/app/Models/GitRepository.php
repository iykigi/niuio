<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitRepository extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'provider', 'url', 'branch', 'credentials', 'install_command',
        'build_command', 'auto_deploy_on_push', 'webhook_secret', 'last_commit_sha',
        'last_deployed_at',
    ];

    protected $hidden = ['credentials', 'webhook_secret'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted',
            'auto_deploy_on_push' => 'boolean',
            'last_deployed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
