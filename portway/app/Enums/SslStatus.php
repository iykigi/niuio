<?php

namespace App\Enums;

enum SslStatus: string
{
    case Pending = 'pending';
    case Issuing = 'issuing';
    case Active = 'active';
    case Renewing = 'renewing';
    case Expired = 'expired';
    case Failed = 'failed';
    case Revoked = 'revoked';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge-success',
            self::Issuing, self::Renewing, self::Pending => 'badge-info',
            self::Expired, self::Failed, self::Revoked => 'badge-danger',
        };
    }
}
