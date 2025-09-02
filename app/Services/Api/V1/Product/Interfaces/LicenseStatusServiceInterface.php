<?php

namespace App\Services\Api\V1\Product\Interfaces;

/**
 * Interface for license status service operations.
 *
 * This interface defines the contract for services that handle
 * license status checking and entitlement information.
 */
interface LicenseStatusServiceInterface
{
    /**
     * Get comprehensive status and entitlements for a license key.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return array|null License key status and entitlements or null if not found
     */
    public function getLicenseKeyStatus(string $licenseKeyUuid): ?array;

    /**
     * Check if a license key is valid and active.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return bool True if license key is valid and active
     */
    public function isLicenseKeyValid(string $licenseKeyUuid): bool;

    /**
     * Get available products and their seat information for a license key.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return array Array of products with seat information
     */
    public function getLicenseKeyEntitlements(string $licenseKeyUuid): array;

    /**
     * Get seat usage information for a license key.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return array Seat usage information including used and available seats
     */
    public function getSeatUsage(string $licenseKeyUuid): array;
}
