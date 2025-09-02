<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Brand\ForceDeactivateSeatsRequest;
use App\Http\Requests\Api\V1\Brand\RenewLicenseRequest;
use App\Http\Requests\Api\V1\Brand\StoreLicenseRequest;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Http\Resources\Api\V1\Brand\LicenseListResource;
use App\Models\Brand;
use App\Models\License;
use App\Services\Api\V1\Brand\LicenseService;
use App\Services\Api\V1\Product\ActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends BaseApiController
{
    public function __construct(
        private LicenseService $licenseService,
        private ActivationService $activationService
    ) {}

    /**
     * Display a listing of licenses for the authenticated brand.
     *
     * @param  Request  $request  The request instance
     * @return JsonResponse Response containing license list
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $brand = $this->getAuthenticatedBrand($request);

            $query = License::whereHas('licenseKey', function ($q) use ($brand) {
                $q->where('brand_id', $brand->id);
            });

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by product
            if ($request->has('product_uuid')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('uuid', $request->product_uuid);
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $licenses = $query->with(['licenseKey', 'product', 'activations'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->successResponse(
                [
                    'licenses' => LicenseListResource::collection($licenses),
                    'pagination' => [
                        'current_page' => $licenses->currentPage(),
                        'last_page' => $licenses->lastPage(),
                        'per_page' => $licenses->perPage(),
                        'total' => $licenses->total(),
                    ],
                ],
                'Licenses retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve licenses: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get summary statistics for licenses.
     *
     * @param  Request  $request  The request instance
     * @return JsonResponse Response containing summary data
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $brand = $this->getAuthenticatedBrand($request);

            $baseQuery = License::whereHas('licenseKey', function ($q) use ($brand) {
                $q->where('brand_id', $brand->id);
            });

            $totalLicenses = $baseQuery->count();
            $validLicenses = (clone $baseQuery)->where('status', 'valid')->count();
            $suspendedLicenses = (clone $baseQuery)->where('status', 'suspended')->count();
            $cancelledLicenses = (clone $baseQuery)->where('status', 'cancelled')->count();
            $expiredLicenses = (clone $baseQuery)->where('status', 'expired')->count();

            return $this->successResponse(
                [
                    'total' => $totalLicenses,
                    'valid' => $validLicenses,
                    'suspended' => $suspendedLicenses,
                    'cancelled' => $cancelledLicenses,
                    'expired' => $expiredLicenses,
                ],
                'License summary retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve license summary: ' . $e->getMessage(),
                500
            );
        }
    }

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

    /**
     * Force deactivate all seats for a license.
     *
     * US5: Brands can force deactivate seats if needed
     */
    public function forceDeactivateSeats(ForceDeactivateSeatsRequest $request, License $license): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        $license = $this->licenseService->findLicenseByUuid($license->uuid, $brand);

        if (! $license) {
            return $this->errorResponse('License not found', 404);
        }

        $reason = $request->validated('reason') ?? 'Administrative deactivation';
        $deactivatedCount = $this->activationService->forceDeactivateAllSeats($license, $reason);

        return $this->successResponse(
            [
                'license_uuid' => $license->uuid,
                'deactivated_seats' => $deactivatedCount,
                'reason' => $reason,
                'deactivated_at' => now()->toISOString(),
            ],
            "Successfully deactivated {$deactivatedCount} seat(s)"
        );
    }
}
