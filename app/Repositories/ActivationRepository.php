<?php

namespace App\Repositories;

use App\Models\Activation;
use App\Models\License;
use App\Repositories\Interfaces\ActivationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository implementation for Activation model operations.
 *
 * This class extends BaseRepository and implements ActivationRepositoryInterface
 * to provide specific database operations for Activation entities.
 */
class ActivationRepository extends BaseRepository implements ActivationRepositoryInterface
{
    /**
     * Get the Activation model instance for this repository.
     *
     * @return Model The Activation model instance
     */
    protected function getModel(): Model
    {
        return new Activation;
    }

    /**
     * Find all activations for a specific license.
     *
     * @param  int  $licenseId  The license ID to search for
     * @return Collection A collection of activations for the license
     */
    public function findByLicenseId(int $licenseId): Collection
    {
        return $this->model->where('license_id', $licenseId)->get();
    }

    /**
     * Find all active activations for a specific license.
     *
     * @param  int  $licenseId  The license ID to search for
     * @return Collection A collection of active activations for the license
     */
    public function findActiveByLicenseId(int $licenseId): Collection
    {
        return $this->model->where('license_id', $licenseId)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Find an activation by license and instance details.
     *
     * This method searches for an activation using multiple criteria.
     * All provided parameters are used to build a query that matches
     * the activation with the most specific criteria.
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
    ): ?Activation {
        $query = $this->model->where('license_id', $license->id);

        if ($instanceId) {
            $query->where('instance_id', $instanceId);
        }

        if ($instanceType) {
            $query->where('instance_type', $instanceType);
        }

        if ($instanceUrl) {
            $query->where('instance_url', $instanceUrl);
        }

        if ($machineId) {
            $query->where('machine_id', $machineId);
        }

        return $query->first();
    }

    /**
     * Get all active activations (status = 'active').
     *
     * Overrides the base getActive() method to use activation-specific status.
     *
     * @return Collection A collection of all active activations
     */
    public function getActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Count the number of active activations for a specific license.
     *
     * This method is useful for seat management to determine how many
     * seats are currently in use for a license.
     *
     * @param  int  $licenseId  The license ID to count activations for
     * @return int The number of active activations for the license
     */
    public function countActiveByLicenseId(int $licenseId): int
    {
        return $this->model->where('license_id', $licenseId)
            ->where('status', 'active')
            ->count();
    }
}
