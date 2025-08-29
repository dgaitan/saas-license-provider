<?php

namespace App\Services\Api\V1\Brand;

use App\Enums\LicenseStatus;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;

class LicenseService
{
    /**
     * Create a new license and associate it with a license key and product.
     */
    public function createLicense(
        Brand $brand,
        string $licenseKeyUuid,
        string $productUuid,
        ?string $expiresAt = null,
        ?int $maxSeats = null
    ): License {
        // Verify license key belongs to brand
        $licenseKey = LicenseKey::where('uuid', $licenseKeyUuid)
            ->where('brand_id', $brand->id)
            ->firstOrFail();

        // Verify product belongs to brand
        $product = Product::where('uuid', $productUuid)
            ->where('brand_id', $brand->id)
            ->firstOrFail();

        return License::create([
            'license_key_id' => $licenseKey->id,
            'product_id' => $product->id,
            'status' => LicenseStatus::VALID,
            'expires_at' => $expiresAt,
            'max_seats' => $maxSeats,
        ]);
    }

    /**
     * Find a license by UUID and verify brand ownership.
     */
    public function findLicenseByUuid(string $uuid, Brand $brand): ?License
    {
        return License::where('uuid', $uuid)
            ->whereHas('licenseKey', function ($query) use ($brand) {
                $query->where('brand_id', $brand->id);
            })
            ->with(['licenseKey', 'product'])
            ->first();
    }

    /**
     * Renew a license by extending its expiration date.
     */
    public function renewLicense(License $license, int $days = 365): License
    {
        $license->renew($days);

        return $license->fresh(['licenseKey', 'product']);
    }

    /**
     * Suspend a license.
     */
    public function suspendLicense(License $license): License
    {
        $license->suspend();

        return $license->fresh(['licenseKey', 'product']);
    }

    /**
     * Resume a suspended license.
     */
    public function resumeLicense(License $license): License
    {
        $license->resume();

        return $license->fresh(['licenseKey', 'product']);
    }

    /**
     * Cancel a license.
     */
    public function cancelLicense(License $license): License
    {
        $license->cancel();

        return $license->fresh(['licenseKey', 'product']);
    }

    /**
     * Get all licenses for a brand.
     */
    public function getLicensesForBrand(Brand $brand): \Illuminate\Database\Eloquent\Collection
    {
        return License::whereHas('licenseKey', function ($query) use ($brand) {
            $query->where('brand_id', $brand->id);
        })
            ->with(['licenseKey', 'product'])
            ->get();
    }
}
