<?php

namespace App\Enums;

enum ActivationStatus: string
{
    case ACTIVE = 'active';
    case DEACTIVATED = 'deactivated';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::DEACTIVATED => 'Deactivated',
            self::EXPIRED => 'Expired',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
