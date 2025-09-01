<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Brand\StoreLicenseKeyRequest;
use App\Http\Resources\Api\V1\LicenseKeyResource;
use App\Models\Brand;
use App\Models\LicenseKey;
use App\Services\Api\V1\Brand\LicenseKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseKeyController extends BaseApiController
{
    public function __construct(
        private LicenseKeyService $licenseKeyService
    ) {}

    /**
     * Store a newly created license key.
     *
     * US1: Brand can provision a license
     */
    public function store(StoreLicenseKeyRequest $request): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $licenseKey = $this->licenseKeyService->createLicenseKey(
            $brand,
            $request->validated('customer_email')
        );

        return $this->successResponse(
            new LicenseKeyResource($licenseKey),
            'License key created successfully',
            201
        );
    }

    /**
     * Display the specified license key.
     */
    public function show(Request $request, LicenseKey $licenseKey): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $licenseKey = $this->licenseKeyService->findLicenseKeyByUuid($licenseKey->uuid, $brand);

        if (! $licenseKey) {
            return $this->errorResponse('License key not found', 404);
        }

        return $this->successResponse(
            new LicenseKeyResource($licenseKey),
            'License key retrieved successfully'
        );
    }
}
