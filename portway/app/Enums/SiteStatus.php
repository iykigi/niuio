<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Suspended = 'suspended';
    case Failed = 'failed';
    case Deleting = 'deleting';

    public function label(): string
    {
        return match ($this) {
            self::Provisioning => 'Creating',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Failed => 'Failed',
            self::Deleting => 'Deleting',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge-success',
            self::Provisioning => 'badge-info',
            self::Suspended => 'badge-warning',
            self::Failed, self::Deleting => 'badge-danger',
        };
    }
}
