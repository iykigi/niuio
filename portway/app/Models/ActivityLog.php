<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Read-only from the application's point of view: rows are only ever
 * appended by App\Services\Security\ActivityLogger, which chains each
 * row's hash to the previous one. Never update() or delete() a row here.
 */
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'target_type', 'target_id', 'description',
        'ip_address', 'user_agent', 'metadata', 'previous_hash', 'hash', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
