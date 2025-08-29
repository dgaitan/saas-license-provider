<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case VALID = 'valid';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'Valid',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }

    public function isActive(): bool
    {
        return $this === self::VALID;
    }
}
