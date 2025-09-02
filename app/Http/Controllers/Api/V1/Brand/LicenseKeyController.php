<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Brand\StoreLicenseKeyRequest;
use App\Http\Resources\Api\V1\LicenseKeyResource;
use App\Http\Resources\Api\V1\Brand\LicenseKeyListResource;
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
     * Display a listing of license keys for the authenticated brand.
     *
     * @param  Request  $request  The request instance
     * @return JsonResponse Response containing license key list
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $brand = $this->getAuthenticatedBrand($request);

            $query = LicenseKey::where('brand_id', $brand->id);

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('is_active', $request->boolean('status'));
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $licenseKeys = $query->with(['licenses.product'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->successResponse(
                [
                    'license_keys' => LicenseKeyListResource::collection($licenseKeys),
                    'pagination' => [
                        'current_page' => $licenseKeys->currentPage(),
                        'last_page' => $licenseKeys->lastPage(),
                        'per_page' => $licenseKeys->perPage(),
                        'total' => $licenseKeys->total(),
                    ],
                ],
                'License keys retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve license keys: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get summary statistics for license keys.
     *
     * @param  Request  $request  The request instance
     * @return JsonResponse Response containing summary data
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $brand = $this->getAuthenticatedBrand($request);

            $totalLicenseKeys = LicenseKey::where('brand_id', $brand->id)->count();
            $activeLicenseKeys = LicenseKey::where('brand_id', $brand->id)->where('is_active', true)->count();
            $inactiveLicenseKeys = $totalLicenseKeys - $activeLicenseKeys;

            return $this->successResponse(
                [
                    'total' => $totalLicenseKeys,
                    'active' => $activeLicenseKeys,
                    'inactive' => $inactiveLicenseKeys,
                ],
                'License key summary retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve license key summary: ' . $e->getMessage(),
                500
            );
        }
    }

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

        // Load the brand relationship to include brand_id in the response
        $licenseKey->load('brand');

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

        // Load the licenses relationship to include them in the response
        $licenseKey->load(['brand', 'licenses']);

        return $this->successResponse(
            new LicenseKeyResource($licenseKey),
            'License key retrieved successfully'
        );
    }
}
