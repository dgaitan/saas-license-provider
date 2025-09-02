<?php

namespace App\Services\Api\V1\Product;

use App\Enums\LicenseStatus;
use App\Models\LicenseKey;
use App\Services\Api\V1\Product\Interfaces\LicenseStatusServiceInterface;

/**
 * Service for handling license status checking and entitlement information.
 *
 * This service provides methods for end-users to check the status and
 * entitlements of their license keys without requiring authentication.
 */
class LicenseStatusService implements LicenseStatusServiceInterface
{
    /**
     * Get comprehensive status and entitlements for a license key.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return array|null License key status and entitlements or null if not found
     */
    public function getLicenseKeyStatus(string $licenseKeyUuid): ?array
    {
        $licenseKey = LicenseKey::with(['licenses.product', 'licenses.activations'])
            ->where('uuid', $licenseKeyUuid)
            ->first();

        if (! $licenseKey) {
            return null;
        }

        return [
            'license_key' => [
                'uuid' => $licenseKey->uuid,
                'key' => $licenseKey->key,
                'customer_email' => $licenseKey->customer_email,
                'is_active' => $licenseKey->isActive(),
                'created_at' => $licenseKey->created_at,
            ],
            'overall_status' => $this->getOverallStatus($licenseKey),
            'entitlements' => $this->getLicenseKeyEntitlements($licenseKeyUuid),
            'seat_usage' => $this->getSeatUsage($licenseKeyUuid),
            'summary' => [
                'total_products' => $licenseKey->licenses->count(),
                'active_licenses' => $licenseKey->licenses
                    ->where('status', LicenseStatus::VALID)
                    ->filter(function ($license) {
                        return $license->expires_at === null || $license->expires_at > now();
                    })
                    ->count(),
                'total_seats' => $licenseKey->licenses
                    ->where('status', LicenseStatus::VALID)
                    ->filter(function ($license) {
                        return $license->expires_at === null || $license->expires_at > now();
                    })
                    ->sum('max_seats'),
                'used_seats' => $licenseKey->licenses
                    ->where('status', LicenseStatus::VALID)
                    ->filter(function ($license) {
                        return $license->expires_at === null || $license->expires_at > now();
                    })
                    ->sum(function ($license) {
                        return $license->activations->where('status', \App\Enums\ActivationStatus::ACTIVE)->count();
                    }),
            ],
        ];
    }

    /**
     * Check if a license key is valid and active.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return bool True if license key is valid and active
     */
    public function isLicenseKeyValid(string $licenseKeyUuid): bool
    {
        $licenseKey = LicenseKey::where('uuid', $licenseKeyUuid)
            ->where('is_active', true)
            ->first();

        if (! $licenseKey) {
            return false;
        }

        // Check if license key has at least one valid license
        return $licenseKey->licenses()
            ->where('status', LicenseStatus::VALID)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get available products and their seat information for a license key.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return array Array of products with seat information
     */
    public function getLicenseKeyEntitlements(string $licenseKeyUuid): array
    {
        $licenseKey = LicenseKey::with(['licenses.product', 'licenses.activations'])
            ->where('uuid', $licenseKeyUuid)
            ->first();

        if (! $licenseKey) {
            return [];
        }

        return $licenseKey->licenses
            ->where('status', LicenseStatus::VALID)
            ->filter(function ($license) {
                return $license->expires_at === null || $license->expires_at > now();
            })
            ->map(function ($license) {
                $activeActivations = $license->activations
                    ->where('status', \App\Enums\ActivationStatus::ACTIVE);

                return [
                    'product' => [
                        'uuid' => $license->product->uuid,
                        'name' => $license->product->name,
                        'slug' => $license->product->slug,
                        'description' => $license->product->description,
                    ],
                    'license' => [
                        'uuid' => $license->uuid,
                        'status' => $license->status->value,
                        'expires_at' => $license->expires_at,
                        'max_seats' => $license->max_seats,
                        'supports_seats' => $license->supportsSeats(),
                    ],
                    'seats' => [
                        'total' => $license->max_seats,
                        'used' => $activeActivations->count(),
                        'available' => $license->max_seats ? ($license->max_seats - $activeActivations->count()) : null,
                        'usage_percentage' => $license->max_seats ? (float) round(($activeActivations->count() / $license->max_seats) * 100, 2) : null,
                    ],
                    'activations' => $activeActivations->map(function ($activation) {
                        return [
                            'instance_id' => $activation->instance_id,
                            'instance_type' => $activation->instance_type,
                            'instance_url' => $activation->instance_url,
                            'machine_id' => $activation->machine_id,
                            'activated_at' => $activation->activated_at,
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get seat usage information for a license key.
     *
     * @param  string  $licenseKeyUuid  The UUID of the license key
     * @return array Seat usage information including used and available seats
     */
    public function getSeatUsage(string $licenseKeyUuid): array
    {
        $licenseKey = LicenseKey::with(['licenses.activations'])
            ->where('uuid', $licenseKeyUuid)
            ->first();

        if (! $licenseKey) {
            return [
                'total_seats' => 0,
                'used_seats' => 0,
                'available_seats' => 0,
                'usage_percentage' => 0,
                'products' => [],
            ];
        }

        $totalSeats = 0;
        $usedSeats = 0;
        $products = [];

        foreach ($licenseKey->licenses as $license) {
            if ($license->status !== LicenseStatus::VALID) {
                continue;
            }

            if ($license->expires_at && $license->expires_at <= now()) {
                continue;
            }

            $licenseSeats = $license->max_seats ?? 0;
            $licenseUsedSeats = $license->activations
                ->where('status', \App\Enums\ActivationStatus::ACTIVE)
                ->count();

            $totalSeats += $licenseSeats;
            $usedSeats += $licenseUsedSeats;

            $products[] = [
                'product_name' => $license->product->name,
                'product_slug' => $license->product->slug,
                'seats' => [
                    'total' => $licenseSeats,
                    'used' => $licenseUsedSeats,
                    'available' => $licenseSeats ? ($licenseSeats - $licenseUsedSeats) : null,
                ],
            ];
        }

        return [
            'total_seats' => $totalSeats,
            'used_seats' => $usedSeats,
            'available_seats' => $totalSeats ? ($totalSeats - $usedSeats) : 0,
            'usage_percentage' => $totalSeats ? (float) round(($usedSeats / $totalSeats) * 100, 2) : 0.0,
            'products' => $products,
        ];
    }

    /**
     * Get the overall status of a license key.
     *
     * @param  LicenseKey  $licenseKey  The license key model
     * @return string Overall status description
     */
    private function getOverallStatus(LicenseKey $licenseKey): string
    {
        if (! $licenseKey->isActive()) {
            return 'inactive';
        }

        // Check if there are any suspended licenses (regardless of expiration)
        $suspendedLicenses = $licenseKey->licenses
            ->where('status', LicenseStatus::SUSPENDED);

        if ($suspendedLicenses->isNotEmpty()) {
            return 'partially_suspended';
        }

        // Check if there are any cancelled licenses
        $cancelledLicenses = $licenseKey->licenses
            ->where('status', LicenseStatus::CANCELLED);

        if ($cancelledLicenses->isNotEmpty()) {
            return 'partially_cancelled';
        }

        $validLicenses = $licenseKey->licenses
            ->where('status', LicenseStatus::VALID)
            ->filter(function ($license) {
                return $license->expires_at === null || $license->expires_at > now();
            });

        if ($validLicenses->isEmpty()) {
            return 'no_valid_licenses';
        }

        return 'active';
    }
}
