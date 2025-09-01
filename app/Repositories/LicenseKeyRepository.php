<?php

namespace App\Repositories;

use App\Models\LicenseKey;
use App\Repositories\Interfaces\LicenseKeyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository implementation for LicenseKey model operations.
 *
 * This class extends BaseRepository and implements LicenseKeyRepositoryInterface
 * to provide specific database operations for LicenseKey entities.
 */
class LicenseKeyRepository extends BaseRepository implements LicenseKeyRepositoryInterface
{
    /**
     * Get the LicenseKey model instance for this repository.
     *
     * @return Model The LicenseKey model instance
     */
    protected function getModel(): Model
    {
        return new LicenseKey;
    }

    /**
     * Find a license key by its key string.
     *
     * @param  string  $key  The license key string to search for
     * @return LicenseKey|null The found license key or null if not found
     */
    public function findByKey(string $key): ?LicenseKey
    {
        return $this->model->where('key', $key)->first();
    }

    /**
     * Find all license keys for a specific customer email.
     *
     * @param  string  $email  The customer email to search for
     * @return Collection A collection of license keys for the customer
     */
    public function findByCustomerEmail(string $email): Collection
    {
        return $this->model->where('customer_email', $email)->get();
    }

    /**
     * Find all license keys for a specific brand.
     *
     * @param  int  $brandId  The brand ID to search for
     * @return Collection A collection of license keys for the brand
     */
    public function findByBrandId(int $brandId): Collection
    {
        return $this->model->where('brand_id', $brandId)->get();
    }

    /**
     * Get a license key with its associated licenses and related data loaded.
     *
     * @param  string  $uuid  The UUID of the license key
     * @return LicenseKey|null The license key with loaded relationships or null if not found
     */
    public function getWithLicenses(string $uuid): ?LicenseKey
    {
        return $this->model->with(['licenses.product', 'licenses.activations'])
            ->where('uuid', $uuid)
            ->first();
    }
}
