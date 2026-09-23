<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileChangeEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['site_id', 'path', 'event', 'hash_before', 'hash_after', 'flagged', 'detected_at'];

    protected function casts(): array
    {
        return [
            'flagged' => 'boolean',
            'detected_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
