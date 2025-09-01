<?php

namespace App\Repositories\Interfaces;

use App\Models\Activation;
use App\Models\License;
use Illuminate\Database\Eloquent\Collection;

interface ActivationRepositoryInterface
{
    /**
     * Find an activation by UUID.
     */
    public function findByUuid(string $uuid): ?Activation;

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
     * Create a new activation.
     */
    public function create(array $data): Activation;

    /**
     * Update an activation.
     */
    public function update(Activation $activation, array $data): bool;

    /**
     * Delete an activation.
     */
    public function delete(Activation $activation): bool;

    /**
     * Get active activations.
     */
    public function getActive(): Collection;

    /**
     * Count active activations for a license.
     */
    public function countActiveByLicenseId(int $licenseId): int;
}
