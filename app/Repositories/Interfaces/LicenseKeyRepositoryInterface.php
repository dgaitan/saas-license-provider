<?php

namespace App\Repositories\Interfaces;

use App\Models\LicenseKey;
use Illuminate\Database\Eloquent\Collection;

interface LicenseKeyRepositoryInterface
{
    /**
     * Find a license key by UUID.
     */
    public function findByUuid(string $uuid): ?LicenseKey;

    /**
     * Find a license key by key string.
     */
    public function findByKey(string $key): ?LicenseKey;

    /**
     * Find license keys by customer email.
     */
    public function findByCustomerEmail(string $email): Collection;

    /**
     * Find license keys by brand ID.
     */
    public function findByBrandId(int $brandId): Collection;

    /**
     * Create a new license key.
     */
    public function create(array $data): LicenseKey;

    /**
     * Update a license key.
     */
    public function update(LicenseKey $licenseKey, array $data): bool;

    /**
     * Delete a license key.
     */
    public function delete(LicenseKey $licenseKey): bool;

    /**
     * Get active license keys.
     */
    public function getActive(): Collection;

    /**
     * Get license keys with their licenses loaded.
     */
    public function getWithLicenses(string $uuid): ?LicenseKey;
}
