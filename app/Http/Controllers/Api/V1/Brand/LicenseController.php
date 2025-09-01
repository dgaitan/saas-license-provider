<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Brand\RenewLicenseRequest;
use App\Http\Requests\Api\V1\Brand\StoreLicenseRequest;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\Brand;
use App\Models\License;
use App\Services\Api\V1\Brand\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends BaseApiController
{
    public function __construct(
        private LicenseService $licenseService
    ) {}

    /**
     * Store a newly created license.
     *
     * US1: Brand can provision a license
     */
    public function store(StoreLicenseRequest $request): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $license = $this->licenseService->createLicense(
            $brand,
            $request->validated('license_key_uuid'),
            $request->validated('product_uuid'),
            $request->validated('expires_at'),
            $request->validated('max_seats')
        );

        if (! $license) {
            return $this->errorResponse('License key or product not found or does not belong to brand', 404);
        }

        return $this->successResponse(
            new LicenseResource($license->load(['licenseKey', 'product'])),
            'License created successfully',
            201
        );
    }

    /**
     * Display the specified license.
     */
    public function show(Request $request, License $license): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $license = $this->licenseService->findLicenseByUuid($license->uuid, $brand);

        if (! $license) {
            return $this->errorResponse('License not found', 404);
        }

        return $this->successResponse(
            new LicenseResource($license),
            'License retrieved successfully'
        );
    }

    /**
     * Renew a license by extending its expiration date.
     *
     * US2: Brand can change license lifecycle
     */
    public function renew(RenewLicenseRequest $request, License $license): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $license = $this->licenseService->findLicenseByUuid($license->uuid, $brand);

        if (! $license) {
            return $this->errorResponse('License not found', 404);
        }

        $days = $request->validated('days') ?? 365;
        $license = $this->licenseService->renewLicense($license, $days);

        return $this->successResponse(
            new LicenseResource($license),
            'License renewed successfully'
        );
    }

    /**
     * Suspend a license.
     *
     * US2: Brand can change license lifecycle
     */
    public function suspend(Request $request, License $license): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $license = $this->licenseService->findLicenseByUuid($license->uuid, $brand);

        if (! $license) {
            return $this->errorResponse('License not found', 404);
        }

        $license = $this->licenseService->suspendLicense($license);

        return $this->successResponse(
            new LicenseResource($license),
            'License suspended successfully'
        );
    }

    /**
     * Resume a suspended license.
     *
     * US2: Brand can change license lifecycle
     */
    public function resume(Request $request, License $license): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $license = $this->licenseService->findLicenseByUuid($license->uuid, $brand);

        if (! $license) {
            return $this->errorResponse('License not found', 404);
        }

        $license = $this->licenseService->resumeLicense($license);

        return $this->successResponse(
            new LicenseResource($license),
            'License resumed successfully'
        );
    }

    /**
     * Cancel a license.
     *
     * US2: Brand can change license lifecycle
     */
    public function cancel(Request $request, License $license): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $license = $this->licenseService->findLicenseByUuid($license->uuid, $brand);

        if (! $license) {
            return $this->errorResponse('License not found', 404);
        }

        $license = $this->licenseService->cancelLicense($license);

        return $this->successResponse(
            new LicenseResource($license),
            'License cancelled successfully'
        );
    }
}
