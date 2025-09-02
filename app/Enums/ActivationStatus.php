<?php

namespace App\Enums;

/**
 * Activation Status Enum
 * 
 * Defines the possible states a license activation can be in.
 * These statuses control whether an activation is currently active
 * and consuming a seat.
 * 
 * @package App\Enums
 */
enum ActivationStatus: string
{
/**
     * Activation is currently active
     * 
     * This status indicates that the license is currently activated
     * for this instance and is consuming a seat.
     */
    case ACTIVE = 'active';

/**
     * Activation has been deactivated
     * 
     * This status indicates that the license was previously activated
     * but has been deactivated, freeing up the seat.
     */
    case DEACTIVATED = 'deactivated';

/**
     * Activation has expired
     * 
     * This status indicates that the activation has expired,
     * likely due to license expiration or seat limit changes.
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
            self::ACTIVE => 'Active',
            self::DEACTIVATED => 'Deactivated',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Check if the activation status is currently active
     * 
     * @return bool True if the activation is currently active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
