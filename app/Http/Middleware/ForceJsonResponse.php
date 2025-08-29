<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force JSON response for API routes
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // Force JSON content type for all API responses
        $response->headers->set('Content-Type', 'application/json');

        // If this is a 404 response and it's an API route, return JSON
        if ($response->getStatusCode() === 404 && $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
                'error' => 'Not Found',
            ], 404);
        }

        return $response;
    }
}
