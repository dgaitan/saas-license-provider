<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Product\ActivateLicenseRequest;
use App\Http\Requests\Api\V1\Product\DeactivateLicenseRequest;
use App\Http\Resources\Api\V1\ActivationResource;
use App\Models\License;
use App\Services\Api\V1\Product\ActivationService;
use Illuminate\Http\JsonResponse;

/**
 * Product-facing API controller for license activation.
 *
 * US3: End-user product can activate a license
 * 
 * @unauthenticated
 */
class ActivationController extends BaseApiController
{
    public function __construct(
        private readonly ActivationService $activationService
    ) {}

    /**
     * Activate a license for a specific instance.
     *
     * US3: End-user product can activate a license
     */
    public function activate(ActivateLicenseRequest $request, License $license): JsonResponse
    {
        $instanceData = $request->validated();

        try {
            $activation = $this->activationService->activateLicense(
                $license,
                $instanceData['instance_id'],
                $instanceData['instance_type'],
                $instanceData['instance_url'] ?? null,
                $instanceData['machine_id'] ?? null
            );

            return $this->successResponse(
                new ActivationResource($activation),
                'License activated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Deactivate a license for a specific instance.
     *
     * US5: End-user product or customer can deactivate a seat
     */
    public function deactivate(DeactivateLicenseRequest $request, License $license): JsonResponse
    {
        $instanceData = $request->validated();

        try {
            $activation = $this->activationService->deactivateLicense(
                $license,
                $instanceData['instance_id'],
                $instanceData['instance_type'],
                $instanceData['instance_url'] ?? null,
                $instanceData['machine_id'] ?? null
            );

            return $this->successResponse(
                new ActivationResource($activation),
                'License deactivated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get activation status for a license and instance.
     */
    public function status(License $license): JsonResponse
    {
        $instanceId = request('instance_id');
        $instanceType = request('instance_type');
        $instanceUrl = request('instance_url');
        $machineId = request('machine_id');

        if (! $instanceId && ! $instanceUrl && ! $machineId) {
            return $this->errorResponse('Instance identifier is required', 400);
        }

        try {
            $activation = $this->activationService->getActivationStatus(
                $license,
                $instanceId,
                $instanceType,
                $instanceUrl,
                $machineId
            );

            if (! $activation) {
                return $this->errorResponse('No activation found for this instance', 404);
            }

            return $this->successResponse(
                new ActivationResource($activation),
                'Activation status retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get seat usage information for a license.
     *
     * US5: End-user product or customer can check seat usage
     */
    public function seatUsage(License $license): JsonResponse
    {
        try {
            $seatUsage = $this->activationService->getSeatUsage($license);

            return $this->successResponse(
                $seatUsage,
                'Seat usage information retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
