<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Database extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'databases';

    protected $fillable = [
        'user_id', 'site_id', 'server_id', 'name', 'engine', 'host', 'port', 'status',
        'size_bytes',
        'size_calculated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function databaseUsers(): HasMany
    {
        return $this->hasMany(DatabaseUser::class);
    }

    public function sizeHuman(): string
    {
        return number_format($this->size_bytes / 1048576, 2).' MB';
    }
}
