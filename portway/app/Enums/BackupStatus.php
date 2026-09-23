<?php

namespace App\Enums;

enum BackupStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Restoring = 'restoring';
    case Restored = 'restored';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed, self::Restored => 'badge-success',
            self::Queued, self::Running, self::Restoring => 'badge-info',
            self::Failed => 'badge-danger',
        };
    }
}
