<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redirect extends Model
{
    protected $fillable = [
        'site_id', 'source_path', 'destination_url', 'status_code',
        'preserve_query_string', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'preserve_query_string' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
