<?php

namespace App\Events;

use App\Models\Backup;
use Illuminate\Foundation\Events\Dispatchable;

class BackupCompleted
{
    use Dispatchable;

    public function __construct(public Backup $backup)
    {
    }
}
