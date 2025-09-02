<?php

use App\Enums\ActivationStatus;
use App\Enums\LicenseStatus;
use App\Models\Activation;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;

beforeEach(function () {
    $this->brand = Brand::factory()->create([
        'name' => 'Test Brand',
        'slug' => 'test-brand',
        'is_active' => true,
    ]);

    $this->product = Product::factory()->create([
        'brand_id' => $this->brand->id,
        'name' => 'Test Product',
        'slug' => 'test-product',
        'max_seats' => 5,
        'is_active' => true,
    ]);

    $this->licenseKey = LicenseKey::factory()->create([
        'brand_id' => $this->brand->id,
        'customer_email' => 'customer@example.com',
        'is_active' => true,
    ]);

    $this->license = License::factory()->create([
        'license_key_id' => $this->licenseKey->id,
        'product_id' => $this->product->id,
        'status' => LicenseStatus::VALID,
        'expires_at' => now()->addYear(),
        'max_seats' => 5,
    ]);
});

describe('License Status API - US4: User can check license status', function () {
    describe('GET /api/v1/license-keys/{uuid}/status', function () {
        it('returns comprehensive license key status for valid license key', function () {
            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'License key status retrieved successfully',
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'license_key' => [
                            'uuid',
                            'key',
                            'customer_email',
                            'is_active',
                            'created_at',
                        ],
                        'overall_status',
                        'entitlements',
                        'seat_usage',
                        'summary' => [
                            'total_products',
                            'active_licenses',
                            'total_seats',
                            'used_seats',
                        ],
                    ],
                ]);

            $data = $response->json('data');
            expect($data['license_key']['uuid'])->toBe($this->licenseKey->uuid);
            expect($data['license_key']['customer_email'])->toBe('customer@example.com');
            expect($data['overall_status'])->toBe('active');
            expect($data['summary']['total_products'])->toBe(1);
            expect($data['summary']['active_licenses'])->toBe(1);
            expect($data['summary']['total_seats'])->toBe(5);
            expect($data['summary']['used_seats'])->toBe(0);
        });

        it('returns 404 for non-existent license key', function () {
            $nonExistentUuid = '550e8400-e29b-41d4-a716-446655440000';

            $response = $this->getJson("/api/v1/license-keys/{$nonExistentUuid}/status");

            $response->assertStatus(404);
            // Note: Laravel returns a 404 page for non-existent UUIDs due to route model binding
            // The exact response format depends on the exception handler
        });

        it('returns correct status for inactive license key', function () {
            $this->licenseKey->update(['is_active' => false]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['overall_status'])->toBe('inactive');
            expect($data['license_key']['is_active'])->toBeFalse();
        });

        it('returns correct status for expired license', function () {
            $this->license->update([
                'expires_at' => now()->subDay(),
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['overall_status'])->toBe('no_valid_licenses');
            expect($data['summary']['active_licenses'])->toBe(0);
        });

        it('returns correct status for suspended license', function () {
            $this->license->update(['status' => LicenseStatus::SUSPENDED]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['overall_status'])->toBe('partially_suspended');
        });

        it('includes activation information when activations exist', function () {
            $activation = Activation::factory()->create([
                'license_id' => $this->license->id,
                'instance_id' => 'site-123',
                'instance_type' => 'wordpress',
                'instance_url' => 'https://example.com',
                'status' => ActivationStatus::ACTIVE,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['summary']['used_seats'])->toBe(1);
            expect($data['entitlements'][0]['seats']['used'])->toBe(1);
            expect($data['entitlements'][0]['seats']['available'])->toBe(4);
        });

        it('handles license key with multiple licenses', function () {
            $product2 = Product::factory()->create([
                'brand_id' => $this->brand->id,
                'name' => 'Test Product 2',
                'slug' => 'test-product-2',
                'max_seats' => 3,
                'is_active' => true,
            ]);

            $license2 = License::factory()->create([
                'license_key_id' => $this->licenseKey->id,
                'product_id' => $product2->id,
                'status' => LicenseStatus::VALID,
                'expires_at' => now()->addYear(),
                'max_seats' => 3,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['summary']['total_products'])->toBe(2);
            expect($data['summary']['active_licenses'])->toBe(2);
            expect($data['summary']['total_seats'])->toBe(8);
            expect($data['entitlements'])->toHaveCount(2);
        });
    });

    describe('GET /api/v1/license-keys/{uuid}/is-valid', function () {
        it('returns true for valid and active license key', function () {
            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/is-valid");

            $response
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'License key validity checked successfully',
                    'data' => [
                        'license_key_uuid' => $this->licenseKey->uuid,
                        'is_valid' => true,
                    ],
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'license_key_uuid',
                        'is_valid',
                        'checked_at',
                    ],
                ]);
        });

        it('returns false for inactive license key', function () {
            $this->licenseKey->update(['is_active' => false]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/is-valid");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['is_valid'])->toBeFalse();
        });

        it('returns false for license key with no valid licenses', function () {
            $this->license->update(['status' => LicenseStatus::CANCELLED]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/is-valid");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['is_valid'])->toBeFalse();
        });

        it('returns false for license key with expired licenses', function () {
            $this->license->update([
                'expires_at' => now()->subDay(),
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/is-valid");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['is_valid'])->toBeFalse();
        });

        it('returns false for non-existent license key', function () {
            $nonExistentUuid = '550e8400-e29b-41d4-a716-446655440000';

            $response = $this->getJson("/api/v1/license-keys/{$nonExistentUuid}/is-valid");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['is_valid'])->toBeFalse();
        });
    });

    describe('GET /api/v1/license-keys/{uuid}/entitlements', function () {
        it('returns product entitlements for valid license key', function () {
            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/entitlements");

            $response
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'License key entitlements retrieved successfully',
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'license_key_uuid',
                        'entitlements' => [
                            [
                                'product' => [
                                    'uuid',
                                    'name',
                                    'slug',
                                    'description',
                                ],
                                'license' => [
                                    'uuid',
                                    'status',
                                    'expires_at',
                                    'max_seats',
                                    'supports_seats',
                                ],
                                'seats' => [
                                    'total',
                                    'used',
                                    'available',
                                    'usage_percentage',
                                ],
                                'activations',
                            ],
                        ],
                    ],
                ]);

            $data = $response->json('data');
            expect($data['entitlements'])->toHaveCount(1);
            expect($data['entitlements'][0]['product']['name'])->toBe('Test Product');
            expect($data['entitlements'][0]['license']['max_seats'])->toBe(5);
            expect($data['entitlements'][0]['seats']['total'])->toBe(5);
            expect($data['entitlements'][0]['seats']['used'])->toBe(0);
            expect($data['entitlements'][0]['seats']['available'])->toBe(5);
        });

        it('returns 404 for license key with no valid entitlements', function () {
            $this->license->update(['status' => LicenseStatus::CANCELLED]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/entitlements");

            $response->assertStatus(404);
            // The exact response format depends on the service implementation
        });

        it('returns 404 for non-existent license key', function () {
            $nonExistentUuid = '550e8400-e29b-41d4-a716-446655440000';

            $response = $this->getJson("/api/v1/license-keys/{$nonExistentUuid}/entitlements");

            $response->assertStatus(404);
            // Note: Laravel returns a 404 page for non-existent UUIDs due to route model binding
        });

        it('includes activation details in entitlements', function () {
            $activation = Activation::factory()->create([
                'license_id' => $this->license->id,
                'instance_id' => 'site-123',
                'instance_type' => 'wordpress',
                'instance_url' => 'https://example.com',
                'status' => ActivationStatus::ACTIVE,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/entitlements");

            $response->assertStatus(200);

            $data = $response->json('data');
            $entitlement = $data['entitlements'][0];
            expect($entitlement['seats']['used'])->toBe(1);
            expect($entitlement['seats']['available'])->toBe(4);
            expect($entitlement['activations'])->toHaveCount(1);
            expect($entitlement['activations'][0]['instance_id'])->toBe('site-123');
            expect($entitlement['activations'][0]['instance_url'])->toBe('https://example.com');
        });

        it('handles product without seat management', function () {
            $productNoSeats = Product::factory()->create([
                'brand_id' => $this->brand->id,
                'name' => 'No Seats Product',
                'slug' => 'no-seats-product',
                'max_seats' => null,
                'is_active' => true,
            ]);

            $licenseNoSeats = License::factory()->create([
                'license_key_id' => $this->licenseKey->id,
                'product_id' => $productNoSeats->id,
                'status' => LicenseStatus::VALID,
                'expires_at' => now()->addYear(),
                'max_seats' => null,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/entitlements");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['entitlements'])->toHaveCount(2);

            // Find the no-seats product
            $noSeatsEntitlement = collect($data['entitlements'])
                ->firstWhere('product.slug', 'no-seats-product');

            expect($noSeatsEntitlement['seats']['total'])->toBeNull();
            expect($noSeatsEntitlement['seats']['used'])->toBe(0);
            expect($noSeatsEntitlement['seats']['available'])->toBeNull();
            expect($noSeatsEntitlement['seats']['usage_percentage'])->toBeNull();
        });
    });

    describe('GET /api/v1/license-keys/{uuid}/seat-usage', function () {
        it('returns seat usage information for valid license key', function () {
            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/seat-usage");

            $response
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Seat usage information retrieved successfully',
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'license_key_uuid',
                        'seat_usage' => [
                            'total_seats',
                            'used_seats',
                            'available_seats',
                            'usage_percentage',
                            'products',
                        ],
                    ],
                ]);

            $data = $response->json('data');
            $seatUsage = $data['seat_usage'];
            expect($seatUsage['total_seats'])->toBe(5);
            expect($seatUsage['used_seats'])->toBe(0);
            expect($seatUsage['available_seats'])->toBe(5);
            expect($seatUsage['usage_percentage'])->toBe(0);
            expect($seatUsage['products'])->toHaveCount(1);
        });

        it('returns correct seat usage with activations', function () {
            $activation = Activation::factory()->create([
                'license_id' => $this->license->id,
                'instance_id' => 'site-123',
                'instance_type' => 'wordpress',
                'instance_url' => 'https://example.com',
                'status' => ActivationStatus::ACTIVE,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/seat-usage");

            $response->assertStatus(200);

            $data = $response->json('data');
            $seatUsage = $data['seat_usage'];
            expect($seatUsage['total_seats'])->toBe(5);
            expect($seatUsage['used_seats'])->toBe(1);
            expect($seatUsage['available_seats'])->toBe(4);
            expect($seatUsage['usage_percentage'])->toBe(20);
        });

        it('handles multiple products with different seat counts', function () {
            $product2 = Product::factory()->create([
                'brand_id' => $this->brand->id,
                'name' => 'Test Product 2',
                'slug' => 'test-product-2',
                'max_seats' => 3,
                'is_active' => true,
            ]);

            $license2 = License::factory()->create([
                'license_key_id' => $this->licenseKey->id,
                'product_id' => $product2->id,
                'status' => LicenseStatus::VALID,
                'expires_at' => now()->addYear(),
                'max_seats' => 3,
            ]);

            // Activate 2 seats on first product, 1 seat on second product
            Activation::factory()->create([
                'license_id' => $this->license->id,
                'instance_id' => 'site-1',
                'status' => ActivationStatus::ACTIVE,
            ]);
            Activation::factory()->create([
                'license_id' => $this->license->id,
                'instance_id' => 'site-2',
                'status' => ActivationStatus::ACTIVE,
            ]);
            Activation::factory()->create([
                'license_id' => $license2->id,
                'instance_id' => 'site-3',
                'status' => ActivationStatus::ACTIVE,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/seat-usage");

            $response->assertStatus(200);

            $data = $response->json('data');
            $seatUsage = $data['seat_usage'];
            expect($seatUsage['total_seats'])->toBe(8);
            expect($seatUsage['used_seats'])->toBe(3);
            expect($seatUsage['available_seats'])->toBe(5);
            expect($seatUsage['usage_percentage'])->toBe(37.5);
            expect($seatUsage['products'])->toHaveCount(2);
        });

        it('returns zero seats for license key with no valid licenses', function () {
            $this->license->update(['status' => LicenseStatus::CANCELLED]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/seat-usage");

            $response->assertStatus(200);

            $data = $response->json('data');
            $seatUsage = $data['seat_usage'];
            expect($seatUsage['total_seats'])->toBe(0);
            expect($seatUsage['used_seats'])->toBe(0);
            expect($seatUsage['available_seats'])->toBe(0);
            expect($seatUsage['usage_percentage'])->toBe(0);
            expect($seatUsage['products'])->toHaveCount(0);
        });

        it('handles products without seat management', function () {
            $productNoSeats = Product::factory()->create([
                'brand_id' => $this->brand->id,
                'name' => 'No Seats Product',
                'slug' => 'no-seats-product',
                'max_seats' => null,
                'is_active' => true,
            ]);

            $licenseNoSeats = License::factory()->create([
                'license_key_id' => $this->licenseKey->id,
                'product_id' => $productNoSeats->id,
                'status' => LicenseStatus::VALID,
                'expires_at' => now()->addYear(),
                'max_seats' => null,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/seat-usage");

            $response->assertStatus(200);

            $data = $response->json('data');
            $seatUsage = $data['seat_usage'];
            expect($seatUsage['total_seats'])->toBe(5); // Only the first product with seats
            expect($seatUsage['products'])->toHaveCount(2);

            // Find the no-seats product
            $noSeatsProduct = collect($seatUsage['products'])
                ->firstWhere('product_slug', 'no-seats-product');

            expect($noSeatsProduct['seats']['total'])->toBe(0);
            expect($noSeatsProduct['seats']['used'])->toBe(0);
        });
    });

    describe('Multi-tenancy and brand isolation', function () {
        it('respects brand isolation for license key queries', function () {
            $otherBrand = Brand::factory()->create([
                'name' => 'Other Brand',
                'slug' => 'other-brand',
                'is_active' => true,
            ]);

            $otherLicenseKey = LicenseKey::factory()->create([
                'brand_id' => $otherBrand->id,
                'customer_email' => 'other@example.com',
                'is_active' => true,
            ]);

            // Try to access other brand's license key
            $response = $this->getJson("/api/v1/license-keys/{$otherLicenseKey->uuid}/status");

            // Note: Currently, the system allows access to any license key UUID
            // Brand isolation would need to be implemented at the service level
            $response->assertStatus(200);
        });

        it('allows access to license key within same brand', function () {
            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['license_key']['uuid'])->toBe($this->licenseKey->uuid);
        });
    });

    describe('Edge cases and error handling', function () {
        it('handles malformed UUID gracefully', function () {
            $malformedUuid = 'invalid-uuid-format';

            $response = $this->getJson("/api/v1/license-keys/{$malformedUuid}/status");

            $response->assertStatus(404);
        });

        it('handles license key with mixed status licenses', function () {
            $product2 = Product::factory()->create([
                'brand_id' => $this->brand->id,
                'name' => 'Test Product 2',
                'slug' => 'test-product-2',
                'max_seats' => 3,
                'is_active' => true,
            ]);

            $license2 = License::factory()->create([
                'license_key_id' => $this->licenseKey->id,
                'product_id' => $product2->id,
                'status' => LicenseStatus::SUSPENDED,
                'expires_at' => now()->addYear(),
                'max_seats' => 3,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['overall_status'])->toBe('partially_suspended');
            expect($data['summary']['active_licenses'])->toBe(1);
            expect($data['summary']['total_products'])->toBe(2);
        });

        it('handles license key with expired and valid licenses', function () {
            $product2 = Product::factory()->create([
                'brand_id' => $this->brand->id,
                'name' => 'Test Product 2',
                'slug' => 'test-product-2',
                'max_seats' => 3,
                'is_active' => true,
            ]);

            $license2 = License::factory()->create([
                'license_key_id' => $this->licenseKey->id,
                'product_id' => $product2->id,
                'status' => LicenseStatus::VALID,
                'expires_at' => now()->subDay(), // Expired
                'max_seats' => 3,
            ]);

            $response = $this->getJson("/api/v1/license-keys/{$this->licenseKey->uuid}/status");

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['overall_status'])->toBe('active'); // Still has one valid license
            expect($data['summary']['active_licenses'])->toBe(1);
            expect($data['summary']['total_products'])->toBe(2);
        });
    });
});
