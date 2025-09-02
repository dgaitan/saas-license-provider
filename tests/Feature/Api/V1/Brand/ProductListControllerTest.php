<?php

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
        'description' => 'This is test product 1',
        'max_seats' => 5,
        'is_active' => true,
    ]);

    $this->product2 = Product::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Test Product 2',
        'slug' => 'test-product-2',
        'description' => 'This is test product 2',
        'max_seats' => 3,
        'is_active' => true,
    ]);

    $this->product3 = Product::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Inactive Product',
        'slug' => 'inactive-product',
        'description' => 'This is an inactive product',
        'max_seats' => 10,
        'is_active' => false,
    ]);

    // Create license keys and licenses to test the license count
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

    // Create licenses for the products
    License::factory()->create([
        'license_key_id' => $this->licenseKey1->id,
        'product_id' => $this->product1->id,
    ]);

    License::factory()->create([
        'license_key_id' => $this->licenseKey1->id,
        'product_id' => $this->product1->id,
    ]);

    License::factory()->create([
        'license_key_id' => $this->licenseKey2->id,
        'product_id' => $this->product2->id,
    ]);
});

describe('ProductListController Endpoints', function () {
    describe('GET /api/v1/products', function () {
        it('returns a list of products for the authenticated brand', function () {
            $response = $this->getJson('/api/v1/products', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'products' => [
                            '*' => [
                                'uuid',
                                'name',
                                'slug',
                                'description',
                                'max_seats',
                                'is_active',
                                'created_at',
                                'updated_at',
                                'licenses_count',
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
                'message' => 'Products retrieved successfully',
                'data' => [
                    'pagination' => [
                        'total' => 3,
                        'per_page' => 15,
                    ],
                ],
            ]);
        });

        it('filters products by active status', function () {
            $response = $this->getJson('/api/v1/products?is_active=true', [
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

            // Verify only active products are returned
            $products = $response->json('data.products');
            foreach ($products as $product) {
                expect($product['is_active'])->toBeTrue();
            }
        });

        it('filters products by inactive status', function () {
            $response = $this->getJson('/api/v1/products?is_active=false', [
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

            // Verify only inactive products are returned
            $products = $response->json('data.products');
            foreach ($products as $product) {
                expect($product['is_active'])->toBeFalse();
            }
        });

        it('filters products by search term', function () {
            $response = $this->getJson('/api/v1/products?search=Product 1', [
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

            $response->assertJsonPath('data.products.0.name', 'Test Product 1');
        });

        it('filters products by search term in description', function () {
            $response = $this->getJson('/api/v1/products?search=inactive', [
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

            $response->assertJsonPath('data.products.0.name', 'Inactive Product');
        });

        it('supports pagination', function () {
            // Create more products to test pagination
            Product::factory()->count(20)->create([
                'brand_id' => $this->brand->id,
            ]);

            $response = $this->getJson('/api/v1/products?per_page=5', [
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
            $response = $this->getJson('/api/v1/products');

            $response->assertStatus(401);
        });

        it('returns 401 when invalid API key is provided', function () {
            $response = $this->getJson('/api/v1/products', [
                'Authorization' => 'Bearer invalid_api_key',
            ]);

            $response->assertStatus(401);
        });

        it('only returns products for the authenticated brand', function () {
            // Create another brand with products
            $otherBrand = Brand::factory()->create([
                'api_key' => 'other_brand_api_key',
                'is_active' => true,
            ]);

            $otherProduct = Product::factory()->create([
                'brand_id' => $otherBrand->id,
                'name' => 'Other Brand Product',
            ]);

            $response = $this->getJson('/api/v1/products', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response->assertStatus(200);

            // Verify the other brand's product is not included
            $products = $response->json('data.products');
            $otherProductFound = collect($products)->contains('name', 'Other Brand Product');
            expect($otherProductFound)->toBeFalse();
        });

        it('includes license count for each product', function () {
            $response = $this->getJson('/api/v1/products', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response->assertStatus(200);

            $products = $response->json('data.products');

            // Product 1 should have 2 licenses
            $product1 = collect($products)->firstWhere('name', 'Test Product 1');
            expect($product1['licenses_count'])->toBe(2);

            // Product 2 should have 1 license
            $product2 = collect($products)->firstWhere('name', 'Test Product 2');
            expect($product2['licenses_count'])->toBe(1);

            // Product 3 should have 0 licenses
            $product3 = collect($products)->firstWhere('name', 'Inactive Product');
            expect($product3['licenses_count'])->toBe(0);
        });
    });

    describe('GET /api/v1/products/summary', function () {
        it('returns summary statistics for products', function () {
            $response = $this->getJson('/api/v1/products/summary', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total_products',
                        'active_products',
                        'inactive_products',
                        'total_seats_capacity',
                        'products_by_seats',
                    ],
                    'message',
                ]);

            $response->assertJson([
                'message' => 'Product summary retrieved successfully',
                'data' => [
                    'total_products' => 3,
                    'active_products' => 2,
                    'inactive_products' => 1,
                    'total_seats_capacity' => 18, // 5 + 3 + 10
                    'products_by_seats' => [
                        'small' => 2, // <= 5 seats (5 and 3)
                        'medium' => 1, // 6-20 seats (10)
                        'large' => 0, // > 20 seats
                    ],
                ],
            ]);
        });

        it('returns 401 when no authorization header is provided', function () {
            $response = $this->getJson('/api/v1/products/summary');

            $response->assertStatus(401);
        });

        it('returns 401 when invalid API key is provided', function () {
            $response = $this->getJson('/api/v1/products/summary', [
                'Authorization' => 'Bearer invalid_api_key',
            ]);

            $response->assertStatus(401);
        });

        it('only counts products for the authenticated brand', function () {
            // Create another brand with products
            $otherBrand = Brand::factory()->create([
                'api_key' => 'other_brand_api_key',
                'is_active' => true,
            ]);

            Product::factory()->count(5)->create([
                'brand_id' => $otherBrand->id,
            ]);

            $response = $this->getJson('/api/v1/products/summary', [
                'Authorization' => 'Bearer brand_test_api_key_123',
            ]);

            $response->assertStatus(200);

            // Verify the count is still only for the authenticated brand
            $response->assertJson([
                'data' => [
                    'total_products' => 3,
                    'active_products' => 2,
                    'inactive_products' => 1,
                ],
            ]);
        });
    });
});
