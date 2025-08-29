<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Brand\StoreLicenseRequest;
use App\Models\Brand;
use App\Models\License;
use App\Services\Api\V1\Brand\LicenseService;
use Illuminate\Http\JsonResponse;

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
        // TODO: Get brand from API key authentication
        $brand = Brand::first(); // Temporary for development

        $license = $this->licenseService->createLicense(
            $brand,
            $request->validated('license_key_uuid'),
            $request->validated('product_uuid'),
            $request->validated('expires_at'),
            $request->validated('max_seats')
        );

        return $this->successResponse(
            $license->load(['licenseKey', 'product'])->toApiArray(),
            'License created successfully',
            201
        );
    }

    /**
     * Display the specified license.
     */
    public function show(License $license): JsonResponse
    {
        // TODO: Get brand from API key authentication and verify ownership
        $brand = Brand::first(); // Temporary for development

        $license = $this->licenseService->findLicenseByUuid($license->uuid, $brand);

        if (!$license) {
            return $this->errorResponse('License not found', 404);
        }

        return $this->successResponse(
            $license->toApiArray(),
            'License retrieved successfully'
        );
    }
}
