<?php

namespace App\Repositories\Interfaces;

use App\Models\Activation;
use App\Models\License;
use Illuminate\Database\Eloquent\Collection;

interface ActivationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find activations by license ID.
     */
    public function findByLicenseId(int $licenseId): Collection;

    /**
     * Find active activations by license ID.
     */
    public function findActiveByLicenseId(int $licenseId): Collection;

    /**
     * Find activation by license and instance details.
     */
    public function findByLicenseAndInstance(
        License $license,
        ?string $instanceId = null,
        ?string $instanceType = null,
        ?string $instanceUrl = null,
        ?string $machineId = null
    ): ?Activation;

    /**
     * Count active activations for a license.
     */
    public function countActiveByLicenseId(int $licenseId): int;
}
