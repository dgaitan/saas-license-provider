<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to authenticate brands using their API tokens.
 *
 * This middleware extracts the Bearer token from the Authorization header
 * and validates it against the brand's API key. If valid, it sets the
 * authenticated brand in the request for use in controllers.
 */
class AuthenticateBrand
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The incoming request
     * @param  Closure  $next  The next middleware in the stack
     * @return Response The response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractTokenFromRequest($request);

        if (! $token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authorization header with Bearer token is required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $brand = Brand::findByApiKey($token);

        if (! $brand) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Set the authenticated brand in the request
        $request->merge(['authenticated_brand' => $brand]);

        return $next($request);
    }

    /**
     * Extract the Bearer token from the Authorization header.
     *
     * @param  Request  $request  The request to extract the token from
     * @return string|null The extracted token or null if not found
     */
    private function extractTokenFromRequest(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (! $authorization || ! str_starts_with($authorization, 'Bearer ')) {
            return null;
        }

        return substr($authorization, 7); // Remove 'Bearer ' prefix
    }
}
