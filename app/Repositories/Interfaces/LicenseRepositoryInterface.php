<?php

namespace App\Repositories\Interfaces;

use App\Models\License;
use Illuminate\Database\Eloquent\Collection;

interface LicenseRepositoryInterface
{
    /**
     * Find a license by UUID.
     */
    public function findByUuid(string $uuid): ?License;

    /**
     * Find licenses by license key ID.
     */
    public function findByLicenseKeyId(int $licenseKeyId): Collection;

    /**
     * Find licenses by product ID.
     */
    public function findByProductId(int $productId): Collection;

    /**
     * Find licenses by brand ID.
     */
    public function findByBrandId(int $brandId): Collection;

    /**
     * Create a new license.
     */
    public function create(array $data): License;

    /**
     * Update a license.
     */
    public function update(License $license, array $data): bool;

    /**
     * Delete a license.
     */
    public function delete(License $license): bool;

    /**
     * Get active licenses.
     */
    public function getActive(): Collection;

    /**
     * Get licenses with their relationships loaded.
     */
    public function getWithRelationships(string $uuid): ?License;

    /**
     * Get licenses by customer email across all brands.
     */
    public function findByCustomerEmail(string $email): Collection;
}
