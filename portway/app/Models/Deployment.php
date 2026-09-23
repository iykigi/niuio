<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'git_repository_id', 'triggered_by', 'trigger', 'commit_sha',
        'commit_message', 'branch', 'status', 'log', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function gitRepository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function appendLog(string $line): void
    {
        $this->update(['log' => $this->log."\n".$line]);
    }
}
