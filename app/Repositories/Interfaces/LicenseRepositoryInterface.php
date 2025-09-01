<?php

namespace App\Repositories\Interfaces;

use App\Models\License;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository interface for License model operations.
 *
 * This interface extends BaseRepositoryInterface and provides
 * specific methods for License-related database operations.
 */
interface LicenseRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find all licenses for a specific license key.
     *
     * @param  int  $licenseKeyId  The license key ID to search for
     * @return Collection A collection of licenses for the license key
     */
    public function findByLicenseKeyId(int $licenseKeyId): Collection;

    /**
     * Find all licenses for a specific product.
     *
     * @param  int  $productId  The product ID to search for
     * @return Collection A collection of licenses for the product
     */
    public function findByProductId(int $productId): Collection;

    /**
     * Find all licenses for a specific brand.
     *
     * @param  int  $brandId  The brand ID to search for
     * @return Collection A collection of licenses for the brand
     */
    public function findByBrandId(int $brandId): Collection;

    /**
     * Get a license with its relationships loaded.
     *
     * @param  string  $uuid  The UUID of the license
     * @return License|null The license with loaded relationships or null if not found
     */
    public function getWithRelationships(string $uuid): ?License;

    /**
     * Find all licenses for a customer email across all brands.
     *
     * @param  string  $email  The customer email to search for
     * @return Collection A collection of licenses for the customer across all brands
     */
    public function findByCustomerEmail(string $email): Collection;
}
