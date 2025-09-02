<?php

use App\Enums\LicenseStatus;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a brand with API key
    $this->brand = Brand::factory()->create([
        'api_key' => 'brand_test_api_key_123',
        'is_active' => true,
    ]);

    // Create products for the brand
    $this->product1 = Product::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Test Product 1',
        'slug' => 'test-product-1',
        'max_seats' => 5,
    ]);

    $this->product2 = Product::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Test Product 2',
        'slug' => 'test-product-2',
        'max_seats' => 3,
    ]);

    // Create license keys for the brand
    $this->licenseKey1 = LicenseKey::factory()->create([
        'brand_id' => $this->brand->id,
        'key' => 'test-key-1',
        'customer_email' => 'user1@example.com',
        'is_active' => true,
    ]);

    $this->licenseKey2 = LicenseKey::factory()->create([
        'brand_id' => $this->brand->id,
        'key' => 'test-key-2',
        'customer_email' => 'user2@example.com',
        'is_active' => true,
    ]);

    $this->licenseKey3 = LicenseKey::factory()->create([
        'brand_id' => $this->brand->id,
        'key' => 'test-key-3',
        'customer_email' => 'user3@example.com',
        'is_active' => false,
    ]);

    // Create licenses for the license keys
    $this->license1 = License::factory()->create([
        'license_key_id' => $this->licenseKey1->id,
        'product_id' => $this->product1->id,
        'status' => LicenseStatus::VALID,
        'max_seats' => 5,
    ]);

    $this->license2 = License::factory()->create([
        'license_key_id' => $this->licenseKey2->id,
        'product_id' => $this->product2->id,
        'status' => LicenseStatus::VALID,
        'max_seats' => 3,
    ]);

    $this->license3 = License::factory()->create([
        'license_key_id' => $this->licenseKey3->id,
        'product_id' => $this->product1->id,
        'status' => LicenseStatus::SUSPENDED,
        'max_seats' => 2,
    ]);
});

describe('LicenseKeyController Listing Endpoints', function () {
    describe('GET /api/v1/license-keys', function () {
        it('returns a list of license keys for the authenticated brand', function () {
            $response = $this->getJson('/api/v1/license-keys', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'license_keys' => [
                            '*' => [
                                'uuid',
                                'key',
                                'customer_email',
                                'is_active',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                        'pagination' => [
                            'current_page',
                            'last_page',
                            'per_page',
                            'total',
                        ],
                    ],
                    'message',
                ]);

            $response->assertJson([
                'message' => 'License keys retrieved successfully',
                'data' => [
                    'pagination' => [
                        'total' => 3,
                        'per_page' => 15,
                    ],
                ],
            ]);
        });

        it('filters license keys by search term', function () {
            $response = $this->getJson('/api/v1/license-keys?search=user1', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'pagination' => [
                            'total' => 1,
                        ],
                    ],
                ]);

            $response->assertJsonPath('data.license_keys.0.customer_email', 'user1@example.com');
        });

        it('filters license keys by status', function () {
            $response = $this->getJson('/api/v1/license-keys?status=true', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'pagination' => [
                            'total' => 2,
                        ],
                    ],
                ]);

            // Verify only active license keys are returned
            $licenseKeys = $response->json('data.license_keys');
            foreach ($licenseKeys as $licenseKey) {
                expect($licenseKey['is_active'])->toBeTrue();
            }
        });

        it('filters license keys by inactive status', function () {
            $response = $this->getJson('/api/v1/license-keys?status=false', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'pagination' => [
                            'total' => 1,
                        ],
                    ],
                ]);

            // Verify only inactive license keys are returned
            $licenseKeys = $response->json('data.license_keys');
            foreach ($licenseKeys as $licenseKey) {
                expect($licenseKey['is_active'])->toBeFalse();
            }
        });

        it('supports pagination', function () {
            // Create more license keys to test pagination
            LicenseKey::factory()->count(20)->create([
                'brand_id' => $this->brand->id,
            ]);

            $response = $this->getJson('/api/v1/license-keys?per_page=5', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'pagination' => [
                            'per_page' => 5,
                            'total' => 23, // 3 original + 20 new
                        ],
                    ],
                ]);
        });

        it('returns 401 when no authorization header is provided', function () {
            $response = $this->getJson('/api/v1/license-keys');

            $response->assertStatus(401);
        });

        it('returns 401 when invalid API key is provided', function () {
            $response = $this->getJson('/api/v1/license-keys', [
                'Authorization' => 'Bearer invalid_api_key',
            ]);

            $response->assertStatus(401);
        });

        it('only returns license keys for the authenticated brand', function () {
            // Create another brand with license keys
            $otherBrand = Brand::factory()->create([
                'api_key' => 'other_brand_api_key',
                'is_active' => true,
            ]);

            $otherLicenseKey = LicenseKey::factory()->create([
                'brand_id' => $otherBrand->id,
                'key' => 'other-brand-key',
                'customer_email' => 'other@example.com',
            ]);

            $response = $this->getJson('/api/v1/license-keys', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response->assertStatus(200);

            // Verify the other brand's license key is not included
            $licenseKeys = $response->json('data.license_keys');
            $otherBrandKeyFound = collect($licenseKeys)->contains('key', 'other-brand-key');
            expect($otherBrandKeyFound)->toBeFalse();
        });
    });

    describe('GET /api/v1/license-keys/summary', function () {
        it('returns summary statistics for license keys', function () {
            $response = $this->getJson('/api/v1/license-keys/summary', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total',
                        'active',
                        'inactive',
                    ],
                    'message',
                ]);

            $response->assertJson([
                'message' => 'License key summary retrieved successfully',
                'data' => [
                    'total' => 3,
                    'active' => 2,
                    'inactive' => 1,
                ],
            ]);
        });

        it('returns 401 when no authorization header is provided', function () {
            $response = $this->getJson('/api/v1/license-keys/summary');

            $response->assertStatus(401);
        });

        it('returns 401 when invalid API key is provided', function () {
            $response = $this->getJson('/api/v1/license-keys/summary', [
                'Authorization' => 'Bearer invalid_api_key',
            ]);

            $response->assertStatus(401);
        });

        it('only counts license keys for the authenticated brand', function () {
            // Create another brand with license keys
            $otherBrand = Brand::factory()->create([
                'api_key' => 'other_brand_api_key',
                'is_active' => true,
            ]);

            LicenseKey::factory()->count(5)->create([
                'brand_id' => $otherBrand->id,
            ]);

            $response = $this->getJson('/api/v1/license-keys/summary', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response->assertStatus(200);

            // Verify the count is still only for the authenticated brand
            $response->assertJson([
                'data' => [
                    'total' => 3,
                    'active' => 2,
                    'inactive' => 1,
                ],
            ]);
        });
    });
});
