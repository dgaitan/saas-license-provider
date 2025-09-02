<?php

namespace App\Enums;

/**
 * License Status Enum
 * 
 * Defines the possible states a license can be in.
 * These statuses control whether a license can be activated and used.
 * 
 * @package App\Enums
 */
enum LicenseStatus: string
{
/**
     * License is valid and can be activated
     * 
     * This status indicates that the license is active, not expired,
     * and can be used to activate products.
     */
    case VALID = 'valid';

/**
     * License is temporarily suspended
     * 
     * This status indicates that the license has been temporarily
     * suspended, likely due to payment issues or policy violations.
     * Cannot be activated until resumed.
     */
    case SUSPENDED = 'suspended';

/**
     * License has been permanently cancelled
     * 
     * This status indicates that the license has been permanently
     * cancelled and cannot be reactivated. A new license must be
     * purchased.
     */
    case CANCELLED = 'cancelled';

/**
     * License has expired
     * 
     * This status indicates that the license has passed its
     * expiration date and cannot be activated. May be renewable.
     */
    case EXPIRED = 'expired';

    /**
     * Get a human-readable label for the status
     * 
     * @return string Human-readable status label
     */
    public function label(): string
    {
        return match ($this) {
            self::VALID => 'Valid',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Check if the license status allows activation
     * 
     * @return bool True if the license can be activated
     */
    public function isActive(): bool
    {
        return $this === self::VALID;
    }
}
