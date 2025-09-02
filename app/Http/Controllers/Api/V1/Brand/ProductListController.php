<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\Brand\ProductListResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for listing products in brand-facing endpoints.
 *
 * This controller provides endpoints for brands to list their products
 * with comprehensive information and filtering capabilities.
 */
class ProductListController extends BaseApiController
{
    /**
     * List all products for the authenticated brand.
     *
     * @param  Request  $request  The request instance
     * @return JsonResponse Response containing product list
     */
    public function index(Request $request): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        try {
            $query = Product::forBrand($brand->id)
                ->withCount('licenses')
                ->orderBy('created_at', 'desc');

            // Apply filters if provided
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return $this->successResponse(
                [
                    'products' => ProductListResource::collection($products),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                    ],
                ],
                'Products retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve products',
                500
            );
        }
    }

    /**
     * Get a summary of products for the authenticated brand.
     *
     * @param  Request  $request  The request instance
     * @return JsonResponse Response containing product summary
     */
    public function summary(Request $request): JsonResponse
    {
        $brand = $this->getAuthenticatedBrand($request);

        try {
            $summary = [
                'total_products' => Product::forBrand($brand->id)->count(),
                'active_products' => Product::forBrand($brand->id)->where('is_active', true)->count(),
                'inactive_products' => Product::forBrand($brand->id)->where('is_active', false)->count(),
                'total_seats_capacity' => Product::forBrand($brand->id)->sum('max_seats'),
                'products_by_seats' => [
                    'small' => Product::forBrand($brand->id)->where('max_seats', '<=', 5)->count(),
                    'medium' => Product::forBrand($brand->id)->whereBetween('max_seats', [6, 20])->count(),
                    'large' => Product::forBrand($brand->id)->where('max_seats', '>', 20)->count(),
                ],
            ];

            return $this->successResponse(
                $summary,
                'Product summary retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve product summary',
                500
            );
        }
    }
}
