<?php

namespace App\Services\Api\V1\Brand;

use App\Models\Brand;
use App\Models\LicenseKey;
use App\Repositories\Interfaces\LicenseKeyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LicenseKeyService
{
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository
    ) {}

    /**
     * Create a new license key for a customer.
     */
    public function createLicenseKey(Brand $brand, string $customerEmail): LicenseKey
    {
        return $this->licenseKeyRepository->create([
            'brand_id' => $brand->id,
            'key' => LicenseKey::generateKey(),
            'customer_email' => $customerEmail,
            'is_active' => true,
        ]);
    }

    /**
     * Find a license key by UUID and verify brand ownership.
     */
    public function findLicenseKeyByUuid(string $uuid, Brand $brand): ?LicenseKey
    {
        $licenseKey = $this->licenseKeyRepository->findByUuid($uuid);

        if (! $licenseKey || $licenseKey->brand_id !== $brand->id) {
            return null;
        }

        return $this->licenseKeyRepository->getWithLicenses($uuid);
    }

    /**
     * Get all license keys for a brand.
     */
    public function getLicenseKeysForBrand(Brand $brand): Collection
    {
        return $this->licenseKeyRepository->findByBrandId($brand->id);
    }

    /**
     * Get license keys by customer email across all brands.
     */
    public function getLicenseKeysByCustomerEmail(string $customerEmail): Collection
    {
        return $this->licenseKeyRepository->findByCustomerEmail($customerEmail);
    }
}
