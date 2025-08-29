<?php

namespace App\Services\Api\V1\Brand;

use App\Models\Brand;
use App\Models\LicenseKey;

class LicenseKeyService
{
    /**
     * Create a new license key for a customer.
     */
    public function createLicenseKey(Brand $brand, string $customerEmail): LicenseKey
    {
        return LicenseKey::create([
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
        return LicenseKey::where('uuid', $uuid)
            ->where('brand_id', $brand->id)
            ->with(['licenses.product'])
            ->first();
    }

    /**
     * Get all license keys for a brand.
     */
    public function getLicenseKeysForBrand(Brand $brand): \Illuminate\Database\Eloquent\Collection
    {
        return LicenseKey::where('brand_id', $brand->id)
            ->with(['licenses.product'])
            ->get();
    }

    /**
     * Get license keys by customer email across all brands.
     */
    public function getLicenseKeysByCustomerEmail(string $customerEmail): \Illuminate\Database\Eloquent\Collection
    {
        return LicenseKey::where('customer_email', $customerEmail)
            ->with(['licenses.product', 'brand'])
            ->get();
    }
}
