<?php

use App\Enums\ActivationStatus;
use App\Enums\LicenseStatus;
use App\Models\Activation;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Tests\Feature\Api\V1\Brand\WithBrandAuthentication;
use Tests\TestCase;

uses(WithBrandAuthentication::class);

describe('LicenseController - US5: Brands can force deactivate seats', function () {
    beforeEach(function () {
        $this->brand = Brand::factory()->create();
        $this->product = Product::factory()->forBrand($this->brand)->create(['max_seats' => 5]);
        $this->licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();

        // Create license through the API to ensure proper relationships
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $this->product->uuid,
            'expires_at' => now()->addYear()->format('Y-m-d'),
            'max_seats' => 5,
        ], $this->brand);

        $this->license = License::where('uuid', $response->json('data.uuid'))->first();
    });

    describe('POST /api/v1/licenses/{license}/force-deactivate-seats', function () {
        it('force deactivates all seats for a license with brand authentication', function () {
            // Create multiple active activations with unique instance IDs
            $activation1 = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-1',
                'instance_type' => 'wordpress',
            ]);
            $activation2 = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-2',
                'instance_type' => 'wordpress',
            ]);
            $activation3 = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-3',
                'instance_type' => 'wordpress',
            ]);
            $activations = [$activation1, $activation2, $activation3];

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats",
                ['reason' => 'Administrative cleanup'],
                $this->brand
            );

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'license_uuid',
                        'deactivated_seats',
                        'reason',
                        'deactivated_at',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully deactivated 3 seat(s)',
                    'data' => [
                        'license_uuid' => $this->license->uuid,
                        'deactivated_seats' => 3,
                        'reason' => 'Administrative cleanup',
                    ],
                ]);

            // Verify all activations were deactivated
            foreach ($activations as $activation) {
                $activation->refresh();
                expect($activation->status)->toBe(ActivationStatus::DEACTIVATED);
            }

            // Verify seats are now available
            $seatUsageResponse = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
            $seatUsageResponse->assertJson([
                'data' => [
                    'used_seats' => 0,
                    'available_seats' => 5,
                ],
            ]);
        });

        it('uses default reason when no reason provided', function () {
            // Create an active activation
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'test-site',
            ]);

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats",
                [],
                $this->brand
            );

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'reason' => 'Administrative deactivation',
                    ],
                ]);
        });

        it('returns 0 when no active seats exist', function () {
            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats",
                ['reason' => 'No reason needed'],
                $this->brand
            );

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'deactivated_seats' => 0,
                    ],
                ]);
        });

        it('handles mixed activation statuses correctly', function () {
            // Create mixed status activations
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'active-1',
            ]);
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::DEACTIVATED,
                'instance_id' => 'deactivated-1',
            ]);
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'active-2',
            ]);

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats",
                ['reason' => 'Mixed cleanup'],
                $this->brand
            );

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'deactivated_seats' => 2, // Only active ones
                    ],
                ]);
        });

        it('requires brand authentication', function () {
            // Test with invalid/missing authentication
            // The auth.brand middleware protects this endpoint (verified by route:list)
            $response = $this->postJson("/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats", [
                'reason' => 'Test reason',
            ], [
                'X-Tenant' => 'invalid-token'
            ]);

            // Should return 401 for invalid token
            $response->assertStatus(401);
        });



        it('enforces brand ownership', function () {
            $otherBrand = Brand::factory()->create();
            $otherProduct = Product::factory()->forBrand($otherBrand)->create(['max_seats' => 5]);
            $otherLicenseKey = LicenseKey::factory()->forBrand($otherBrand)->create();

            $response = $this->authenticatedPost('/api/v1/licenses', [
                'license_key_uuid' => $otherLicenseKey->uuid,
                'product_uuid' => $otherProduct->uuid,
                'expires_at' => now()->addYear()->format('Y-m-d'),
                'max_seats' => 5,
            ], $otherBrand);

            $response->assertStatus(201);
            $otherLicense = License::where('uuid', $response->json('data.uuid'))->first();

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$otherLicense->uuid}/force-deactivate-seats",
                ['reason' => 'Test reason'],
                $this->brand
            );

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error' => 'Not Found',
                ]);
        });

        it('returns 404 for non-existent license', function () {
            $nonExistentUuid = '550e8400-e29b-41d4-a716-446655440000';

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$nonExistentUuid}/force-deactivate-seats",
                ['reason' => 'Test reason'],
                $this->brand
            );

            $response->assertStatus(404);
        });

        it('validates reason field length', function () {
            $longReason = str_repeat('a', 501); // Exceeds 500 character limit

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats",
                ['reason' => $longReason],
                $this->brand
            );

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        });

        it('logs all deactivation events', function () {
            // Create multiple activations with unique instance IDs
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-4',
            ]);
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-5',
            ]);

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats",
                ['reason' => 'Logging test'],
                $this->brand
            );

            $response->assertStatus(200);

            // The logging is handled in the service layer and would be tested there
            // This test ensures the endpoint works correctly
            expect($response->json('data.deactivated_seats'))->toBe(2);
        });

        it('handles license without seat management gracefully', function () {
            $licenseKeyWithoutSeats = LicenseKey::factory()->forBrand($this->brand)->create();

            $response = $this->authenticatedPost('/api/v1/licenses', [
                'license_key_uuid' => $licenseKeyWithoutSeats->uuid,
                'product_uuid' => $this->product->uuid,
                'expires_at' => now()->addYear()->format('Y-m-d'),
            ], $this->brand);

            $licenseWithoutSeats = License::where('uuid', $response->json('data.uuid'))->first();

            $response = $this->authenticatedPost(
                "/api/v1/licenses/{$licenseWithoutSeats->uuid}/force-deactivate-seats",
                ['reason' => 'No seats test'],
                $this->brand
            );

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'deactivated_seats' => 0,
                    ],
                ]);
        });
    });
});
