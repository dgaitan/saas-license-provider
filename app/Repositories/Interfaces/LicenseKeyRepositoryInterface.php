<?php

namespace App\Repositories\Interfaces;

use App\Models\LicenseKey;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository interface for LicenseKey model operations.
 *
 * This interface extends BaseRepositoryInterface and provides
 * specific methods for LicenseKey-related database operations.
 */
interface LicenseKeyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a license key by its key string.
     *
     * @param  string  $key  The license key string to search for
     * @return LicenseKey|null The found license key or null if not found
     */
    public function findByKey(string $key): ?LicenseKey;

    /**
     * Find all license keys for a specific customer email.
     *
     * @param  string  $email  The customer email to search for
     * @return Collection A collection of license keys for the customer
     */
    public function findByCustomerEmail(string $email): Collection;

    /**
     * Find all license keys for a specific brand.
     *
     * @param  int  $brandId  The brand ID to search for
     * @return Collection A collection of license keys for the brand
     */
    public function findByBrandId(int $brandId): Collection;

    /**
     * Get a license key with its associated licenses and related data loaded.
     *
     * @param  string  $uuid  The UUID of the license key
     * @return LicenseKey|null The license key with loaded relationships or null if not found
     */
    public function getWithLicenses(string $uuid): ?LicenseKey;
}
