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
        'is_active' => true,
    ]);

    // Create licenses for the license keys
    $this->license1 = License::factory()
        ->forLicenseKey($this->licenseKey1)
        ->forProduct($this->product1)
        ->withStatus(LicenseStatus::VALID)
        ->withSeats(5)
        ->create([
            'expires_at' => now()->addYear(),
        ]);

    $this->license2 = License::factory()
        ->forLicenseKey($this->licenseKey2)
        ->forProduct($this->product2)
        ->withStatus(LicenseStatus::VALID)
        ->withSeats(3)
        ->create([
            'expires_at' => now()->addYear(),
        ]);

    $this->license3 = License::factory()
        ->forLicenseKey($this->licenseKey3)
        ->forProduct($this->product1)
        ->withStatus(LicenseStatus::SUSPENDED)
        ->withSeats(2)
        ->create([
            'expires_at' => now()->addYear(),
        ]);

    $this->license4 = License::factory()
        ->forLicenseKey($this->licenseKey1)
        ->forProduct($this->product2)
        ->withStatus(LicenseStatus::CANCELLED)
        ->withSeats(1)
        ->create([
            'expires_at' => now()->addYear(),
        ]);
});

describe('LicenseController Listing Endpoints', function () {
    describe('GET /api/v1/licenses', function () {
        it('returns a list of licenses for the authenticated brand', function () {
            $response = $this->getJson('/api/v1/licenses', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'licenses' => [
                            '*' => [
                                'uuid',
                                'status',
                                'expires_at',
                                'max_seats',
                                'created_at',
                                'updated_at',
                                'license_key' => [
                                    'uuid',
                                    'key',
                                    'customer_email',
                                ],
                                'product' => [
                                    'uuid',
                                    'name',
                                    'slug',
                                ],
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
                'message' => 'Licenses retrieved successfully',
                'data' => [
                    'pagination' => [
                        'total' => 4,
                        'per_page' => 15,
                    ],
                ],
            ]);
        });

        it('filters licenses by search term', function () {
            $response = $this->getJson('/api/v1/licenses?search=Product 1', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'pagination' => [
                            'total' => 2, // 2 licenses for Product 1
                        ],
                    ],
                ]);

            // Verify only Product 1 licenses are returned
            $licenses = $response->json('data.licenses');
            foreach ($licenses as $license) {
                expect($license['product']['name'])->toBe('Test Product 1');
            }
        });

        it('filters licenses by status', function () {
            $response = $this->getJson('/api/v1/licenses?status=valid', [
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

            // Verify only valid licenses are returned
            $licenses = $response->json('data.licenses');
            foreach ($licenses as $license) {
                expect($license['status'])->toBe('valid');
            }
        });

        it('filters licenses by product UUID', function () {
            $response = $this->getJson("/api/v1/licenses?product_uuid={$this->product1->uuid}", [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'pagination' => [
                            'total' => 2, // 2 licenses for Product 1
                        ],
                    ],
                ]);

            // Verify only Product 1 licenses are returned
            $licenses = $response->json('data.licenses');
            foreach ($licenses as $license) {
                expect($license['product']['uuid'])->toBe($this->product1->uuid);
            }
        });

        it('supports pagination', function () {
            // Create more licenses to test pagination
            License::factory()->count(20)->create([
                'license_key_id' => $this->licenseKey1->id,
                'product_id' => $this->product1->id,
                'status' => LicenseStatus::VALID,
            ]);

            $response = $this->getJson('/api/v1/licenses?per_page=5', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'pagination' => [
                            'per_page' => 5,
                            'total' => 24, // 4 original + 20 new
                        ],
                    ],
                ]);
        });

        it('returns 401 when no authorization header is provided', function () {
            $response = $this->getJson('/api/v1/licenses');

            $response->assertStatus(401);
        });

        it('returns 401 when invalid API key is provided', function () {
            $response = $this->getJson('/api/v1/licenses', [
                'Authorization' => 'Bearer invalid_api_key',
            ]);

            $response->assertStatus(401);
        });

        it('only returns licenses for the authenticated brand', function () {
            // Create another brand with licenses
            $otherBrand = Brand::factory()->create([
                'api_key' => 'other_brand_api_key',
                'is_active' => true,
            ]);

            $otherProduct = Product::factory()->create([
                'brand_id' => $otherBrand->id,
            ]);

            $otherLicenseKey = LicenseKey::factory()->create([
                'brand_id' => $otherBrand->id,
            ]);

            $otherLicense = License::factory()->create([
                'license_key_id' => $otherLicenseKey->id,
                'product_id' => $otherProduct->id,
                'status' => LicenseStatus::VALID,
            ]);

            $response = $this->getJson('/api/v1/licenses', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response->assertStatus(200);

            // Verify the other brand's license is not included
            $licenses = $response->json('data.licenses');
            $otherLicenseFound = collect($licenses)->contains('uuid', $otherLicense->uuid);
            expect($otherLicenseFound)->toBeFalse();
        });
    });

    describe('GET /api/v1/licenses/summary', function () {
        it('returns summary statistics for licenses', function () {
            $response = $this->getJson('/api/v1/licenses/summary', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total',
                        'valid',
                        'suspended',
                        'cancelled',
                        'expired',
                    ],
                    'message',
                ]);

            $response->assertJson([
                'message' => 'License summary retrieved successfully',
                'data' => [
                    'total' => 4,
                    'valid' => 2,
                    'suspended' => 1,
                    'cancelled' => 1,
                    'expired' => 0,
                ],
            ]);
        });

        it('returns 401 when no authorization header is provided', function () {
            $response = $this->getJson('/api/v1/licenses/summary');

            $response->assertStatus(401);
        });

        it('returns 401 when invalid API key is provided', function () {
            $response = $this->getJson('/api/v1/licenses/summary', [
                'Authorization' => 'Bearer invalid_api_key',
            ]);

            $response->assertStatus(401);
        });

        it('only counts licenses for the authenticated brand', function () {
            // Create another brand with licenses
            $otherBrand = Brand::factory()->create([
                'api_key' => 'other_brand_api_key',
                'is_active' => true,
            ]);

            $otherProduct = Product::factory()->create([
                'brand_id' => $otherBrand->id,
            ]);

            $otherLicenseKey = LicenseKey::factory()->create([
                'brand_id' => $otherBrand->id,
            ]);

            License::factory()->count(5)->create([
                'license_key_id' => $otherLicenseKey->id,
                'product_id' => $otherProduct->id,
                'status' => LicenseStatus::VALID,
            ]);

            $response = $this->getJson('/api/v1/licenses/summary', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response->assertStatus(200);

            // Verify the count is still only for the authenticated brand
            $response->assertJson([
                'data' => [
                    'total' => 4,
                    'valid' => 2,
                    'suspended' => 1,
                    'cancelled' => 1,
                    'expired' => 0,
                ],
            ]);
        });
    });
});
