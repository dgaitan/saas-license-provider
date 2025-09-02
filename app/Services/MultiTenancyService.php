<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for handling multi-tenancy operations across the license service.
 *
 * This service provides centralized methods for managing brand isolation,
 * cross-brand operations, and multi-tenant data access patterns.
 */
class MultiTenancyService
{
    /**
     * Get all brands in the system.
     */
    public function getAllBrands(): Collection
    {
        return Brand::all();
    }

    /**
     * Get all active brands in the system.
     */
    public function getActiveBrands(): Collection
    {
        return Brand::where('is_active', true)->get();
    }

    /**
     * Get all products for a specific brand.
     *
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function getProductsForBrand(Brand|int $brand): Collection
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return Product::forBrand($brandId)->active()->get();
    }

    /**
     * Get all license keys for a specific brand.
     *
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function getLicenseKeysForBrand(Brand|int $brand): Collection
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return LicenseKey::forBrand($brandId)->active()->get();
    }

    /**
     * Get all licenses for a specific brand.
     *
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function getLicensesForBrand(Brand|int $brand): Collection
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return License::forBrand($brandId)->get();
    }

    /**
     * Get all license keys for a customer email across all brands.
     *
     * @param  string  $email  The customer email
     */
    public function getLicenseKeysForCustomer(string $email): Collection
    {
        return LicenseKey::where('customer_email', $email)
            ->with(['brand', 'licenses.product', 'licenses.activations'])
            ->get();
    }

    /**
     * Get all license keys for a customer email within a specific brand.
     *
     * @param  string  $email  The customer email
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function getLicenseKeysForCustomerInBrand(string $email, Brand|int $brand): Collection
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return LicenseKey::forBrand($brandId)
            ->where('customer_email', $email)
            ->with(['licenses.product'])
            ->get();
    }

    /**
     * Get all licenses for a customer email across all brands.
     *
     * @param  string  $email  The customer email
     */
    public function getLicensesForCustomer(string $email): Collection
    {
        return License::whereHas('licenseKey', function ($query) use ($email) {
            $query->where('customer_email', $email);
        })->with(['licenseKey.brand', 'product', 'activations'])->get();
    }

    /**
     * Get all licenses for a customer email within a specific brand.
     *
     * @param  string  $email  The customer email
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function getLicensesForCustomerInBrand(string $email, Brand|int $brand): Collection
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return License::forBrand($brandId)
            ->whereHas('licenseKey', function ($query) use ($email) {
                $query->where('customer_email', $email);
            })
            ->with(['licenseKey.brand', 'product', 'activations'])
            ->get();
    }

    /**
     * Get brand statistics for a specific brand.
     *
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function getBrandStatistics(Brand|int $brand): array
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return [
            'products_count' => Product::forBrand($brandId)->count(),
            'active_products_count' => Product::forBrand($brandId)->active()->count(),
            'license_keys_count' => LicenseKey::forBrand($brandId)->count(),
            'active_license_keys_count' => LicenseKey::forBrand($brandId)->active()->count(),
            'licenses_count' => License::forBrand($brandId)->count(),
            'valid_licenses_count' => License::forBrand($brandId)
                ->where('status', \App\Enums\LicenseStatus::VALID)
                ->count(),
            'total_seats' => License::forBrand($brandId)
                ->where('status', \App\Enums\LicenseStatus::VALID)
                ->sum('max_seats'),
        ];
    }

    /**
     * Get customer statistics across all brands.
     *
     * @param  string  $email  The customer email
     */
    public function getCustomerStatistics(string $email): array
    {
        return [
            'brands_count' => LicenseKey::where('customer_email', $email)
                ->distinct('brand_id')
                ->count('brand_id'),
            'license_keys_count' => LicenseKey::where('customer_email', $email)->count(),
            'licenses_count' => License::whereHas('licenseKey', function ($query) use ($email) {
                $query->where('customer_email', $email);
            })->count(),
            'valid_licenses_count' => License::whereHas('licenseKey', function ($query) use ($email) {
                $query->where('customer_email', $email);
            })->where('status', \App\Enums\LicenseStatus::VALID)->count(),
            'total_seats' => License::whereHas('licenseKey', function ($query) use ($email) {
                $query->where('customer_email', $email);
            })->where('status', \App\Enums\LicenseStatus::VALID)->sum('max_seats'),
        ];
    }

    /**
     * Check if a customer has access to a specific product across any brand.
     *
     * @param  string  $email  The customer email
     * @param  string  $productSlug  The product slug
     */
    public function customerHasProductAccess(string $email, string $productSlug): bool
    {
        return License::whereHas('licenseKey', function ($query) use ($email) {
            $query->where('customer_email', $email);
        })->whereHas('product', function ($query) use ($productSlug) {
            $query->where('slug', $productSlug);
        })->where('status', \App\Enums\LicenseStatus::VALID)->exists();
    }

    /**
     * Check if a customer has access to a specific product within a brand.
     *
     * @param  string  $email  The customer email
     * @param  string  $productSlug  The product slug
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function customerHasProductAccessInBrand(string $email, string $productSlug, Brand|int $brand): bool
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return License::forBrand($brandId)
            ->whereHas('licenseKey', function ($query) use ($email) {
                $query->where('customer_email', $email);
            })->whereHas('product', function ($query) use ($productSlug) {
                $query->where('slug', $productSlug);
            })->where('status', \App\Enums\LicenseStatus::VALID)->exists();
    }

    /**
     * Get all brands that a customer has licenses with.
     *
     * @param  string  $email  The customer email
     */
    public function getCustomerBrands(string $email): Collection
    {
        return Brand::whereHas('licenseKeys', function ($query) use ($email) {
            $query->where('customer_email', $email);
        })->get();
    }

    /**
     * Validate that a license key belongs to a specific brand.
     *
     * @param  string  $licenseKeyUuid  The license key UUID
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function validateLicenseKeyBrandOwnership(string $licenseKeyUuid, Brand|int $brand): bool
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return LicenseKey::where('uuid', $licenseKeyUuid)
            ->where('brand_id', $brandId)
            ->exists();
    }

    /**
     * Validate that a product belongs to a specific brand.
     *
     * @param  string  $productUuid  The product UUID
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function validateProductBrandOwnership(string $productUuid, Brand|int $brand): bool
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return Product::where('uuid', $productUuid)
            ->where('brand_id', $brandId)
            ->exists();
    }

    /**
     * Validate that a license belongs to a specific brand.
     *
     * @param  string  $licenseUuid  The license UUID
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function validateLicenseBrandOwnership(string $licenseUuid, Brand|int $brand): bool
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return License::where('uuid', $licenseUuid)
            ->whereHas('licenseKey', function ($query) use ($brandId) {
                $query->where('brand_id', $brandId);
            })->exists();
    }
}
