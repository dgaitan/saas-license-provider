<?php

use function Pest\Laravel\get;

describe('API Documentation', function () {
    it('can access the documentation page', function () {
        $response = get('/docs/api');

        $response->assertStatus(200);
        $response->assertSee('License Service API Documentation');
        $response->assertSee('@stoplight/elements');
    });

    it('can access the OpenAPI specification JSON', function () {
        $response = get('/docs/api.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();

        // Verify OpenAPI structure
        expect($data)->toHaveKey('openapi');
        expect($data)->toHaveKey('info');
        expect($data)->toHaveKey('paths');
        expect($data)->toHaveKey('servers');

        // Verify our custom info
        expect($data['info']['title'])->toBe('License Service API Documentation');
        expect($data['info']['version'])->toBe('1.0.0');
        expect($data['info']['description'])->toContain('Centralized License Service API');

        // Verify servers configuration
        expect($data['servers'])->toHaveCount(2);
        expect($data['servers'][0]['description'])->toBe('Local Development');
        expect($data['servers'][0]['url'])->toBe('http://localhost:8002/api');
    });

    it('includes our API endpoints in the documentation', function () {
        $response = get('/docs/api.json');
        $data = $response->json();

        // Check that we have paths documented
        expect($data['paths'])->not->toBeEmpty();

        // Check for some key endpoints
        $paths = array_keys($data['paths']);

        // Should have license endpoints
        expect($paths)->toContain('/v1/licenses/{license}/activate');
        expect($paths)->toContain('/v1/licenses/{license}/deactivate');

        // Should have license key endpoints
        expect($paths)->toContain('/v1/license-keys');
        expect($paths)->toContain('/v1/license-keys/{licenseKey}');

        // Should have product endpoints
        expect($paths)->toContain('/v1/products');
        expect($paths)->toContain('/v1/products/summary');

        // Should have cross-brand endpoints
        expect($paths)->toContain('/v1/customers/licenses');

        // Should have license status endpoints
        expect($paths)->toContain('/v1/license-keys/{licenseKeyUuid}/status');
    });
});
