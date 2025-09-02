<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\CrossBrand\CustomerLicenseSummaryResource;
use App\Services\MultiTenancyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for cross-brand operations.
 *
 * US6: Brands can list licenses by customer email across all brands
 * This controller provides endpoints for cross-brand license queries
 * that require brand authentication but can access data across brands.
 */
class CrossBrandController extends BaseApiController
{
    public function __construct(
        private readonly MultiTenancyService $multiTenancyService
    ) {}

    /**
     * List all licenses for a customer email across all brands.
     *
     * US6: Brands can list licenses by customer email across all brands
     * This endpoint allows brands to see what licenses a customer has
     * across the entire ecosystem, not just their own brand.
     *
     * @param  Request  $request  The request instance
     * @return JsonResponse Response containing customer license summary
     */
    public function listLicensesByCustomer(Request $request): JsonResponse
    {
        // Validate customer email from query parameters
        $validator = Validator::make($request->query(), [
            'customer_email' => 'required|email|max:255',
        ], [
            'customer_email.required' => 'Customer email is required to list licenses.',
            'customer_email.email' => 'Customer email must be a valid email address.',
            'customer_email.max' => 'Customer email cannot exceed 255 characters.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $customerEmail = $request->query('customer_email');

        try {
            // Get all license keys for the customer across all brands
            $licenseKeys = $this->multiTenancyService->getLicenseKeysForCustomer($customerEmail);

            // Get all licenses for the customer across all brands
            $licenses = $this->multiTenancyService->getLicensesForCustomer($customerEmail);

            // Get all brands the customer has licenses with
            $customerBrands = $this->multiTenancyService->getCustomerBrands($customerEmail);

            // Create comprehensive customer license summary
            $customerSummary = [
                'customer_email' => $customerEmail,
                'total_license_keys' => $licenseKeys->count(),
                'total_licenses' => $licenses->count(),
                'brands_count' => $customerBrands->count(),
                'brands' => $customerBrands->map(fn($brand) => [
                    'uuid' => $brand->uuid,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'domain' => $brand->domain,
                ]),
                'license_keys' => CustomerLicenseSummaryResource::collection($licenseKeys),
                'licenses_summary' => [
                    'total_active' => $licenses->where('status', 'valid')->count(),
                    'total_suspended' => $licenses->where('status', 'suspended')->count(),
                    'total_cancelled' => $licenses->where('status', 'cancelled')->count(),
                    'total_expired' => $licenses->where('status', 'expired')->count(),
                ],
                'products_summary' => $licenses->groupBy('product.slug')
                    ->map(function ($productLicenses, $productSlug) {
                        $product = $productLicenses->first()->product;
                        return [
                            'product_slug' => $productSlug,
                            'product_name' => $product->name,
                            'brand_name' => $product->brand->name,
                            'licenses_count' => $productLicenses->count(),
                            'total_seats' => $productLicenses->sum('max_seats'),
                            'active_seats' => $productLicenses->sum(function ($license) {
                                return $license->activations->where('status', 'active')->count();
                            }),
                        ];
                    })->values(),
            ];

            return $this->successResponse(
                $customerSummary,
                "Successfully retrieved license information for customer {$customerEmail}"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve customer license information',
                500
            );
        }
    }

    /**
     * Get a summary of customer licenses within a specific brand.
     *
     * This endpoint allows brands to see what licenses a customer has
     * specifically within their own brand.
     *
     * @param  Request  $request  The request instance for brand authentication and customer email
     * @return JsonResponse Response containing brand-specific customer license summary
     */
    public function listLicensesByCustomerInBrand(Request $request): JsonResponse
    {
        // Validate customer email from query parameters
        $validator = Validator::make($request->query(), [
            'customer_email' => 'required|email|max:255',
        ], [
            'customer_email.required' => 'Customer email is required to list licenses.',
            'customer_email.email' => 'Customer email must be a valid email address.',
            'customer_email.max' => 'Customer email cannot exceed 255 characters.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $customerEmail = $request->query('customer_email');
        $brand = $this->getAuthenticatedBrand($request);

        try {
            // Get license keys for the customer within the authenticated brand
            $licenseKeys = $this->multiTenancyService->getLicenseKeysForCustomerInBrand($customerEmail, $brand);

            // Get licenses for the customer within the authenticated brand
            $licenses = $this->multiTenancyService->getLicensesForCustomerInBrand($customerEmail, $brand);

            // Create brand-specific customer license summary
            $brandSummary = [
                'customer_email' => $customerEmail,
                'brand' => [
                    'uuid' => $brand->uuid,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'domain' => $brand->domain,
                ],
                'license_keys_count' => $licenseKeys->count(),
                'licenses_count' => $licenses->count(),
                'license_keys' => CustomerLicenseSummaryResource::collection($licenseKeys),
                'licenses_summary' => [
                    'total_active' => $licenses->where('status', 'valid')->count(),
                    'total_suspended' => $licenses->where('status', 'suspended')->count(),
                    'total_cancelled' => $licenses->where('status', 'cancelled')->count(),
                    'total_expired' => $licenses->where('status', 'expired')->count(),
                ],
                'products_summary' => $licenses->groupBy('product.slug')
                    ->map(function ($productLicenses, $productSlug) {
                        $product = $productLicenses->first()->product;
                        return [
                            'product_slug' => $productSlug,
                            'product_name' => $product->name,
                            'licenses_count' => $productLicenses->count(),
                            'total_seats' => $productLicenses->sum('max_seats'),
                            'active_seats' => $productLicenses->sum(function ($license) {
                                return $license->activations->where('status', 'active')->count();
                            }),
                        ];
                    })->values(),
            ];

            return $this->successResponse(
                $brandSummary,
                "Successfully retrieved license information for customer {$customerEmail} in brand {$brand->name}"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve customer license information for brand',
                500
            );
        }
    }
}
