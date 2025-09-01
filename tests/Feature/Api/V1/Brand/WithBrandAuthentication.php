<?php

namespace Tests\Feature\Api\V1\Brand;

use App\Models\Brand;

/**
 * Trait to provide brand authentication helpers for API tests.
 */
trait WithBrandAuthentication
{
    /**
     * Add Authorization header with brand API key to the request.
     *
     * @param  Brand|null  $brand  The brand to authenticate with
     * @return array The headers array
     */
    protected function withBrandAuth(?Brand $brand = null): array
    {
        $brand = $brand ?? $this->brand;

        return ['Authorization' => 'Bearer ' . $brand->api_key];
    }

    /**
     * Make a request with brand authentication.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The URI to request
     * @param  array  $data  The request data
     * @param  Brand|null  $brand  The brand to authenticate with
     * @return \Illuminate\Testing\TestResponse
     */
    protected function authenticatedRequest(string $method, string $uri, array $data = [], ?Brand $brand = null)
    {
        return $this->withHeaders($this->withBrandAuth($brand))
            ->$method($uri, $data);
    }

    /**
     * Make a POST request with brand authentication.
     *
     * @param  string  $uri  The URI to request
     * @param  array  $data  The request data
     * @param  Brand|null  $brand  The brand to authenticate with
     * @return \Illuminate\Testing\TestResponse
     */
    protected function authenticatedPost(string $uri, array $data = [], ?Brand $brand = null)
    {
        return $this->authenticatedRequest('postJson', $uri, $data, $brand);
    }

    /**
     * Make a GET request with brand authentication.
     *
     * @param  string  $uri  The URI to request
     * @param  Brand|null  $brand  The brand to authenticate with
     * @return \Illuminate\Testing\TestResponse
     */
    protected function authenticatedGet(string $uri, ?Brand $brand = null)
    {
        return $this->authenticatedRequest('getJson', $uri, [], $brand);
    }

    /**
     * Make a PATCH request with brand authentication.
     *
     * @param  string  $uri  The URI to request
     * @param  array  $data  The request data
     * @param  Brand|null  $brand  The brand to authenticate with
     * @return \Illuminate\Testing\TestResponse
     */
    protected function authenticatedPatch(string $uri, array $data = [], ?Brand $brand = null)
    {
        return $this->authenticatedRequest('patchJson', $uri, $data, $brand);
    }
}
