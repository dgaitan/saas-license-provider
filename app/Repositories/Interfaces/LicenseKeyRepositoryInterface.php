<?php

namespace App\Repositories\Interfaces;

use App\Models\LicenseKey;
use Illuminate\Database\Eloquent\Collection;

interface LicenseKeyRepositoryInterface extends BaseRepositoryInterface
{
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
     * Get license keys with their licenses loaded.
     */
    public function getWithLicenses(string $uuid): ?LicenseKey;
}
