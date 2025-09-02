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

describe('ActivationController - US5: End-user product or customer can deactivate a seat', function () {
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

    describe('GET /api/v1/licenses/{license}/seat-usage', function () {
        it('returns seat usage information for license with seat management', function () {
            // Create some activations with unique instance IDs
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-1',
                'instance_type' => 'wordpress',
            ]);
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-2',
                'instance_type' => 'wordpress',
            ]);
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'site-3',
                'instance_type' => 'wordpress',
            ]);

            $response = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'supports_seat_management',
                        'total_seats',
                        'used_seats',
                        'available_seats',
                        'usage_percentage',
                        'active_activations' => [
                            '*' => [
                                'id',
                                'instance_id',
                                'instance_type',
                                'instance_url',
                                'machine_id',
                                'activated_at',
                                'last_activity',
                            ],
                        ],
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'supports_seat_management' => true,
                        'total_seats' => 5,
                        'used_seats' => 3,
                        'available_seats' => 2,
                        'usage_percentage' => 60.0,
                    ],
                ]);

            expect($response->json('data.active_activations'))->toHaveCount(3);
        });

        it('returns appropriate message for license without seat management', function () {
            $licenseKeyWithoutSeats = LicenseKey::factory()->forBrand($this->brand)->create();

            $response = $this->authenticatedPost('/api/v1/licenses', [
                'license_key_uuid' => $licenseKeyWithoutSeats->uuid,
                'product_uuid' => $this->product->uuid,
                'expires_at' => now()->addYear()->format('Y-m-d'),
            ], $this->brand);

            $licenseWithoutSeats = License::where('uuid', $response->json('data.uuid'))->first();

            $response = $this->getJson("/api/v1/licenses/{$licenseWithoutSeats->uuid}/seat-usage");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'supports_seat_management' => false,
                        'message' => 'This license does not support seat management',
                    ],
                ]);
        });

        it('handles license with no active activations', function () {
            $response = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'supports_seat_management' => true,
                        'total_seats' => 5,
                        'used_seats' => 0,
                        'available_seats' => 5,
                        'usage_percentage' => 0.0,
                    ],
                ]);

            expect($response->json('data.active_activations'))->toHaveCount(0);
        });

        it('includes detailed activation information', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'test-instance-123',
                'instance_type' => 'wordpress',
                'instance_url' => 'https://example.com',
                'machine_id' => 'machine-456',
            ]);

            $response = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");

            $response->assertStatus(200);

            $activationData = $response->json('data.active_activations.0');
            expect($activationData['instance_id'])->toBe('test-instance-123');
            expect($activationData['instance_type'])->toBe('wordpress');
            expect($activationData['instance_url'])->toBe('https://example.com');
            expect($activationData['machine_id'])->toBe('machine-456');
            expect($activationData)->toHaveKey('activated_at');
            expect($activationData)->toHaveKey('last_activity');
        });

        it('returns 404 for non-existent license', function () {
            $nonExistentUuid = '550e8400-e29b-41d4-a716-446655440000';

            $response = $this->getJson("/api/v1/licenses/{$nonExistentUuid}/seat-usage");

            $response->assertStatus(404);
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

            $response = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'used_seats' => 2, // Only active ones
                        'available_seats' => 3,
                        'usage_percentage' => 40.0,
                    ],
                ]);

            expect($response->json('data.active_activations'))->toHaveCount(2);
        });
    });

    describe('POST /api/v1/licenses/{license}/deactivate - Enhanced US5 functionality', function () {
        it('deactivates license and frees up seat', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'test-site',
                'instance_type' => 'wordpress',
            ]);

            $response = $this->postJson("/api/v1/licenses/{$this->license->uuid}/deactivate", [
                'instance_id' => 'test-site',
                'instance_type' => 'wordpress',
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'uuid',
                        'instance_id',
                        'status',
                        'status_label',
                        'activated_at',
                        'deactivated_at',
                        'created_at',
                        'updated_at',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'License deactivated successfully',
                    'data' => [
                        'instance_id' => 'test-site',
                        'status' => ActivationStatus::DEACTIVATED->value,
                    ],
                ]);

            // Verify the activation was deactivated
            $activation->refresh();
            expect($activation->status)->toBe(ActivationStatus::DEACTIVATED);
            expect($activation->deactivated_at)->not->toBeNull();

            // Verify seat is now available
            $seatUsageResponse = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
            $seatUsageResponse->assertJson([
                'data' => [
                    'used_seats' => 0,
                    'available_seats' => 5,
                ],
            ]);
        });

        it('returns 400 when no activation found for instance', function () {
            $response = $this->postJson("/api/v1/licenses/{$this->license->uuid}/deactivate", [
                'instance_id' => 'non-existent-site',
                'instance_type' => 'wordpress',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'No activation found for this instance',
                ]);
        });

        it('returns 400 when activation is already deactivated', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::DEACTIVATED,
                'instance_id' => 'already-deactivated',
                'instance_type' => 'wordpress',
            ]);

            $response = $this->postJson("/api/v1/licenses/{$this->license->uuid}/deactivate", [
                'instance_id' => 'already-deactivated',
                'instance_type' => 'wordpress',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'License is not currently activated for this instance',
                ]);
        });

        it('validates required fields', function () {
            $response = $this->postJson("/api/v1/licenses/{$this->license->uuid}/deactivate", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['instance_id', 'instance_type']);
        });

        it('handles deactivation with optional fields', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'test-site',
                'instance_type' => 'wordpress',
                'instance_url' => 'https://example.com',
                'machine_id' => 'machine-123',
            ]);

            $response = $this->postJson("/api/v1/licenses/{$this->license->uuid}/deactivate", [
                'instance_id' => 'test-site',
                'instance_type' => 'wordpress',
                'instance_url' => 'https://example.com',
                'machine_id' => 'machine-123',
            ]);

            $response->assertStatus(200);

            // Verify the activation was deactivated
            $activation->refresh();
            expect($activation->status)->toBe(ActivationStatus::DEACTIVATED);
        });
    });
});
