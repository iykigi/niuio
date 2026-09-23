<?php

namespace App\Models;

use App\Enums\BackupStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'user_id', 'initiated_by', 'type', 'trigger', 'status',
        'disk', 'path', 'size_bytes', 'failure_reason', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BackupStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function sizeHuman(): string
    {
        return $this->size_bytes ? number_format($this->size_bytes / 1048576, 1).' MB' : '—';
    }
}
