<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Brand\StoreLicenseKeyRequest;
use App\Models\Brand;
use App\Models\LicenseKey;
use Illuminate\Http\JsonResponse;

class LicenseKeyController extends BaseApiController
{
    /**
     * Store a newly created license key.
     * 
     * US1: Brand can provision a license
     */
    public function store(StoreLicenseKeyRequest $request): JsonResponse
    {
        // TODO: Get brand from API key authentication
        $brand = Brand::first(); // Temporary for development

        $licenseKey = LicenseKey::create([
            'brand_id' => $brand->id,
            'key' => LicenseKey::generateKey(),
            'customer_email' => $request->validated('customer_email'),
            'is_active' => true,
        ]);

        return $this->successResponse(
            $licenseKey->toApiArray(),
            'License key created successfully',
            201
        );
    }

    /**
     * Display the specified license key.
     */
    public function show(LicenseKey $licenseKey): JsonResponse
    {
        return $this->successResponse(
            $licenseKey->load(['licenses.product'])->toApiArray(),
            'License key retrieved successfully'
        );
    }
}
