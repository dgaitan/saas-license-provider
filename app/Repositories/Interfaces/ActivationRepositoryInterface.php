<?php

namespace App\Repositories\Interfaces;

use App\Models\Activation;
use App\Models\License;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository interface for Activation model operations.
 *
 * This interface extends BaseRepositoryInterface and provides
 * specific methods for Activation-related database operations.
 */
interface ActivationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find all activations for a specific license.
     *
     * @param  int  $licenseId  The license ID to search for
     * @return Collection A collection of activations for the license
     */
    public function findByLicenseId(int $licenseId): Collection;

    /**
     * Find all active activations for a specific license.
     *
     * @param  int  $licenseId  The license ID to search for
     * @return Collection A collection of active activations for the license
     */
    public function findActiveByLicenseId(int $licenseId): Collection;

    /**
     * Find an activation by license and instance details.
     *
     * @param  License  $license  The license to search within
     * @param  string|null  $instanceId  The instance ID to search for
     * @param  string|null  $instanceType  The instance type to search for
     * @param  string|null  $instanceUrl  The instance URL to search for
     * @param  string|null  $machineId  The machine ID to search for
     * @return Activation|null The found activation or null if not found
     */
    public function findByLicenseAndInstance(
        License $license,
        ?string $instanceId = null,
        ?string $instanceType = null,
        ?string $instanceUrl = null,
        ?string $machineId = null
    ): ?Activation;

    /**
     * Count the number of active activations for a specific license.
     *
     * @param  int  $licenseId  The license ID to count activations for
     * @return int The number of active activations for the license
     */
    public function countActiveByLicenseId(int $licenseId): int;
}
