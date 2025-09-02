<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\Api\V1\Product\Interfaces\LicenseStatusServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * Controller for handling license status checking (US4).
 *
 * This controller provides public endpoints for end-users to check
 * the status and entitlements of their license keys without requiring
 * authentication.
 * 
 * @unauthenticated
 */
class LicenseStatusController extends BaseApiController
{
    /**
     * Create a new controller instance.
     *
     * @param  LicenseStatusServiceInterface  $licenseStatusService  The license status service
     */
    public function __construct(
        private readonly LicenseStatusServiceInterface $licenseStatusService
    ) {}

    /**
     * Get comprehensive status and entitlements for a license key.
     *
     * @param  string  $licenseKeyUuid  The license key UUID from route
     * @return JsonResponse JSON response with license key status
     */
    public function status(string $licenseKeyUuid): JsonResponse
    {
        $status = $this->licenseStatusService->getLicenseKeyStatus($licenseKeyUuid);

        if (! $status) {
            return $this->errorResponse(
                'License key not found',
                404
            );
        }

        return $this->successResponse(
            $status,
            'License key status retrieved successfully'
        );
    }

    /**
     * Check if a license key is valid and active.
     *
     * @param  string  $licenseKeyUuid  The license key UUID from route
     * @return JsonResponse JSON response with validity status
     */
    public function isValid(string $licenseKeyUuid): JsonResponse
    {
        $isValid = $this->licenseStatusService->isLicenseKeyValid($licenseKeyUuid);

        return $this->successResponse(
            [
                'license_key_uuid' => $licenseKeyUuid,
                'is_valid' => $isValid,
                'checked_at' => now()->toISOString(),
            ],
            'License key validity checked successfully'
        );
    }

    /**
     * Get entitlements for a license key.
     *
     * @param  string  $licenseKeyUuid  The license key UUID from route
     * @return JsonResponse JSON response with entitlements
     */
    public function entitlements(string $licenseKeyUuid): JsonResponse
    {
        $entitlements = $this->licenseStatusService->getLicenseKeyEntitlements($licenseKeyUuid);

        if (empty($entitlements)) {
            return $this->errorResponse(
                'License key not found or has no valid entitlements',
                404
            );
        }

        return $this->successResponse(
            [
                'license_key_uuid' => $licenseKeyUuid,
                'entitlements' => $entitlements,
            ],
            'License key entitlements retrieved successfully'
        );
    }

    /**
     * Get seat usage information for a license key.
     *
     * @param  string  $licenseKeyUuid  The license key UUID from route
     * @return JsonResponse JSON response with seat usage
     */
    public function seatUsage(string $licenseKeyUuid): JsonResponse
    {
        $seatUsage = $this->licenseStatusService->getSeatUsage($licenseKeyUuid);

        return $this->successResponse(
            [
                'license_key_uuid' => $licenseKeyUuid,
                'seat_usage' => $seatUsage,
            ],
            'Seat usage information retrieved successfully'
        );
    }
}
