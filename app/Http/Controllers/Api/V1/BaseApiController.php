<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    /**
     * Return a successful JSON response.
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message = 'Error', int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Get the authenticated brand from the request.
     *
     * @param  Request  $request  The request instance
     * @return Brand The authenticated brand
     */
    protected function getAuthenticatedBrand(Request $request): Brand
    {
        $brand = $request->get('authenticated_brand');

        if (!$brand) {
            throw new \Illuminate\Auth\AuthenticationException('Brand authentication required');
        }

        return $brand;
    }
}
