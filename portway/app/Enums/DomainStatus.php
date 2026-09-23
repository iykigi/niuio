<?php

namespace App\Enums;

enum DomainStatus: string
{
    case PendingDns = 'pending_dns';
    case DnsDetected = 'dns_detected';
    case Connected = 'connected';
    case SslInstalling = 'ssl_installing';
    case Active = 'active';
    case Failed = 'failed';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PendingDns => 'DNS Pending',
            self::DnsDetected => 'DNS Detected',
            self::Connected => 'Domain Connected',
            self::SslInstalling => 'SSL Installing',
            self::Active => 'Active',
            self::Failed => 'Failed',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge-success',
            self::Connected, self::DnsDetected, self::SslInstalling => 'badge-info',
            self::PendingDns => 'badge-warning',
            self::Failed, self::Suspended => 'badge-danger',
        };
    }
}
