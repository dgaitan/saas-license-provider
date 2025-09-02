<?php

namespace App\Services\Api\V1\Brand;

use App\Enums\LicenseStatus;
use App\Models\Brand;
use App\Models\License;
use App\Models\Product;
use App\Repositories\Interfaces\LicenseKeyRepositoryInterface;
use App\Repositories\Interfaces\LicenseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LicenseService
{
    public function __construct(
        private readonly LicenseRepositoryInterface $licenseRepository,
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository
    ) {}

    /**
     * Create a new license and associate it with a license key and product.
     */
    public function createLicense(
        Brand $brand,
        string $licenseKeyUuid,
        string $productUuid,
        ?string $expiresAt = null,
        ?int $maxSeats = null
    ): ?License {
        // Verify license key belongs to brand
        $licenseKey = $this->licenseKeyRepository->findByUuid($licenseKeyUuid);
        if (! $licenseKey || $licenseKey->brand_id !== $brand->id) {
            return null;
        }

        // Verify product belongs to brand
        $product = Product::where('uuid', $productUuid)
            ->where('brand_id', $brand->id)
            ->first();

        if (! $product) {
            return null;
        }

        return $this->licenseRepository->create([
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
        $license = $this->licenseRepository->findByUuid($uuid);

        if (! $license) {
            return null;
        }

        // Verify brand ownership
        $licenseKey = $this->licenseKeyRepository->findByUuid($license->licenseKey->uuid);
        if (! $licenseKey || $licenseKey->brand_id !== $brand->id) {
            return null;
        }

        return $this->licenseRepository->getWithRelationships($uuid);
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
     * Renew a license to a specific expiration date.
     */
    public function renewLicenseToDate(License $license, string $expiresAt): License
    {
        $license->update(['expires_at' => $expiresAt]);

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
    public function getLicensesForBrand(Brand $brand): Collection
    {
        return $this->licenseRepository->findByBrandId($brand->id);
    }
}
