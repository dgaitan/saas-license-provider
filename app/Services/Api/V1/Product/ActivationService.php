<?php

namespace App\Services\Api\V1\Product;

use App\Enums\ActivationStatus;
use App\Models\Activation;
use App\Models\License;
use App\Repositories\Interfaces\ActivationRepositoryInterface;
use Illuminate\Support\Facades\Log;

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
     * US5: End-user product or customer can deactivate a seat
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

        // US5: Deactivate the seat and free up capacity
        $activation->deactivate();

        // Log the seat deactivation for audit purposes
        Log::info('Seat deactivated', [
            'license_id' => $license->id,
            'instance_id' => $instanceId,
            'instance_type' => $instanceType,
            'deactivated_at' => now(),
            'available_seats_before' => $license->getAvailableSeats() + 1,
            'available_seats_after' => $license->getAvailableSeats(),
        ]);

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

    /**
     * Get seat usage information for a license.
     *
     * US5: End-user product or customer can check seat usage
     *
     * @param  License  $license  The license to check
     * @return array Seat usage information
     */
    public function getSeatUsage(License $license): array
    {
        if (! $license->supportsSeatManagement()) {
            return [
                'supports_seat_management' => false,
                'message' => 'This license does not support seat management',
            ];
        }

        $activeActivations = $license->activations()
            ->where('status', ActivationStatus::ACTIVE)
            ->get();

        $totalSeats = $license->max_seats;
        $usedSeats = $activeActivations->count();
        $availableSeats = $totalSeats - $usedSeats;
        $usagePercentage = $totalSeats > 0 ? round(($usedSeats / $totalSeats) * 100, 2) : 0;

        return [
            'supports_seat_management' => true,
            'total_seats' => $totalSeats,
            'used_seats' => $usedSeats,
            'available_seats' => $availableSeats,
            'usage_percentage' => $usagePercentage,
            'active_activations' => $activeActivations->map(function ($activation) {
                return [
                    'id' => $activation->id,
                    'instance_id' => $activation->instance_id,
                    'instance_type' => $activation->instance_type,
                    'instance_url' => $activation->instance_url,
                    'machine_id' => $activation->machine_id,
                    'activated_at' => $activation->activated_at,
                    'last_activity' => $activation->updated_at,
                ];
            }),
        ];
    }

    /**
     * Force deactivate all activations for a license (admin/brand function).
     *
     * US5: Brands can force deactivate seats if needed
     *
     * @param  License  $license  The license to deactivate all seats for
     * @param  string  $reason  Reason for deactivation
     * @return int Number of deactivated seats
     */
    public function forceDeactivateAllSeats(License $license, string $reason = 'Administrative deactivation'): int
    {
        $activeActivations = $license->activations()
            ->where('status', ActivationStatus::ACTIVE)
            ->get();

        $deactivatedCount = 0;

        foreach ($activeActivations as $activation) {
            $activation->deactivate();
            $activation->update(['deactivation_reason' => $reason]);
            $deactivatedCount++;

            Log::info('Seat force deactivated', [
                'license_id' => $license->id,
                'activation_id' => $activation->id,
                'instance_id' => $activation->instance_id,
                'reason' => $reason,
                'deactivated_at' => now(),
            ]);
        }

        return $deactivatedCount;
    }
}
