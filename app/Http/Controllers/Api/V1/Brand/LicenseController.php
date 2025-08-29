<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Enums\LicenseStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends BaseApiController
{
    /**
     * Store a newly created license.
     * 
     * US1: Brand can provision a license
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'license_key_uuid' => 'required|string|exists:license_keys,uuid',
            'product_uuid' => 'required|string|exists:products,uuid',
            'expires_at' => 'nullable|date|after:now',
            'max_seats' => 'nullable|integer|min:1',
        ]);

        // TODO: Get brand from API key authentication
        $brand = Brand::first(); // Temporary for development

        // Verify license key belongs to brand
        $licenseKey = LicenseKey::where('uuid', $request->license_key_uuid)
            ->where('brand_id', $brand->id)
            ->firstOrFail();

        // Verify product belongs to brand
        $product = Product::where('uuid', $request->product_uuid)
            ->where('brand_id', $brand->id)
            ->firstOrFail();

        $license = License::create([
            'license_key_id' => $licenseKey->id,
            'product_id' => $product->id,
            'status' => LicenseStatus::VALID,
            'expires_at' => $request->expires_at,
            'max_seats' => $request->max_seats,
        ]);

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
        return $this->successResponse(
            $license->load(['licenseKey', 'product'])->toApiArray(),
            'License retrieved successfully'
        );
    }
}
