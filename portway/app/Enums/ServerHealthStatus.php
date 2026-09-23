<?php

namespace App\Enums;

/**
 * Not a database-backed status — computed at read time from the latest
 * ServerMetric row and thresholds, for the admin Server Health cards.
 */
enum ServerHealthStatus: string
{
    case Healthy = 'healthy';
    case Warning = 'warning';
    case Critical = 'critical';
    case Offline = 'offline';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Healthy => 'badge-success',
            self::Warning => 'badge-warning',
            self::Critical, self::Offline => 'badge-danger',
        };
    }
}
