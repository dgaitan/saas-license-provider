<?php

namespace App\Services\Api\V1\Product;

use App\Enums\ActivationStatus;
use App\Models\Activation;
use App\Models\License;
use App\Repositories\Interfaces\ActivationRepositoryInterface;

/**
 * Service for handling license activation business logic.
 *
 * US3: End-user product can activate a license
 */
class ActivationService
{
    public function __construct(
        private readonly ActivationRepositoryInterface $activationRepository
    ) {}

    /**
     * Activate a license for a specific instance.
     *
     * @param  License  $license  The license to activate
     * @param  string  $instanceId  The instance identifier
     * @param  string  $instanceType  The type of instance (wordpress, machine, etc.)
     * @param  string|null  $instanceUrl  The instance URL (optional)
     * @param  string|null  $machineId  The machine ID (optional)
     *
     * @throws \Exception If activation fails
     */
    public function activateLicense(
        License $license,
        string $instanceId,
        string $instanceType,
        ?string $instanceUrl = null,
        ?string $machineId = null
    ): Activation {
        // Validate license is active and not expired
        if (! $license->isValid()) {
            throw new \Exception('License is not valid or has expired');
        }

        // Check if license supports seat management
        if ($license->supportsSeatManagement()) {
            // Check if there are available seats
            if ($license->getAvailableSeats() <= 0) {
                throw new \Exception('No available seats for this license');
            }
        }

        // Check if instance is already activated for this license
        $existingActivation = $this->activationRepository->findByLicenseAndInstance(
            $license,
            $instanceId,
            $instanceType,
            $instanceUrl,
            $machineId
        );

        if ($existingActivation) {
            if ($existingActivation->isActive()) {
                throw new \Exception('License is already activated for this instance');
            }

            // Reactivate the existing activation
            $existingActivation->activate();

            return $existingActivation->fresh();
        }

        // Create new activation
        $activation = $this->activationRepository->create([
            'license_id' => $license->id,
            'instance_id' => $instanceId,
            'instance_type' => $instanceType,
            'instance_url' => $instanceUrl,
            'machine_id' => $machineId,
            'status' => ActivationStatus::ACTIVE,
            'activated_at' => now(),
        ]);

        return $activation->fresh();
    }

    /**
     * Deactivate a license for a specific instance.
     *
     * @param  License  $license  The license to deactivate
     * @param  string  $instanceId  The instance identifier
     * @param  string  $instanceType  The type of instance
     * @param  string|null  $instanceUrl  The instance URL (optional)
     * @param  string|null  $machineId  The machine ID (optional)
     *
     * @throws \Exception If deactivation fails
     */
    public function deactivateLicense(
        License $license,
        string $instanceId,
        string $instanceType,
        ?string $instanceUrl = null,
        ?string $machineId = null
    ): Activation {
        $activation = $this->activationRepository->findByLicenseAndInstance(
            $license,
            $instanceId,
            $instanceType,
            $instanceUrl,
            $machineId
        );

        if (! $activation) {
            throw new \Exception('No activation found for this instance');
        }

        if (! $activation->isActive()) {
            throw new \Exception('License is not currently activated for this instance');
        }

        $activation->deactivate();

        return $activation->fresh();
    }

    /**
     * Get activation status for a license and instance.
     *
     * @param  License  $license  The license
     * @param  string|null  $instanceId  The instance identifier
     * @param  string|null  $instanceType  The type of instance
     * @param  string|null  $instanceUrl  The instance URL
     * @param  string|null  $machineId  The machine ID
     */
    public function getActivationStatus(
        License $license,
        ?string $instanceId = null,
        ?string $instanceType = null,
        ?string $instanceUrl = null,
        ?string $machineId = null
    ): ?Activation {
        return $this->activationRepository->findByLicenseAndInstance(
            $license,
            $instanceId,
            $instanceType,
            $instanceUrl,
            $machineId
        );
    }
}
