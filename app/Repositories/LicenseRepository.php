<?php

namespace App\Repositories;

use App\Models\License;
use App\Repositories\Interfaces\LicenseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository implementation for License model operations.
 *
 * This class extends BaseRepository and implements LicenseRepositoryInterface
 * to provide specific database operations for License entities.
 */
class LicenseRepository extends BaseRepository implements LicenseRepositoryInterface
{
    /**
     * Get the License model instance for this repository.
     *
     * @return Model The License model instance
     */
    protected function getModel(): Model
    {
        return new License;
    }

    /**
     * Find all licenses for a specific license key.
     *
     * @param  int  $licenseKeyId  The license key ID to search for
     * @return Collection A collection of licenses for the license key
     */
    public function findByLicenseKeyId(int $licenseKeyId): Collection
    {
        return $this->model->where('license_key_id', $licenseKeyId)->get();
    }

    /**
     * Find all licenses for a specific product.
     *
     * @param  int  $productId  The product ID to search for
     * @return Collection A collection of licenses for the product
     */
    public function findByProductId(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }

    /**
     * Find all licenses for a specific brand.
     *
     * @param  int  $brandId  The brand ID to search for
     * @return Collection A collection of licenses for the brand
     */
    public function findByBrandId(int $brandId): Collection
    {
        return $this->model->whereHas('licenseKey', function ($query) use ($brandId) {
            $query->where('brand_id', $brandId);
        })->get();
    }

    /**
     * Get all active licenses (status = 'valid').
     *
     * Overrides the base getActive() method to use license-specific status.
     *
     * @return Collection A collection of all active licenses
     */
    public function getActive(): Collection
    {
        return $this->model->where('status', 'valid')->get();
    }

    /**
     * Get a license with its relationships loaded.
     *
     * @param  string  $uuid  The UUID of the license
     * @return License|null The license with loaded relationships or null if not found
     */
    public function getWithRelationships(string $uuid): ?License
    {
        return $this->model->with(['licenseKey', 'product', 'activations'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Find all licenses for a customer email across all brands.
     *
     * @param  string  $email  The customer email to search for
     * @return Collection A collection of licenses for the customer across all brands
     */
    public function findByCustomerEmail(string $email): Collection
    {
        return $this->model->whereHas('licenseKey', function ($query) use ($email) {
            $query->where('customer_email', $email);
        })->with(['licenseKey.brand', 'product'])->get();
    }
}
