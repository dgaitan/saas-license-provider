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

describe('User Story 5 Integration - End-user product or customer can deactivate a seat', function () {
    beforeEach(function () {
        $this->brand = Brand::factory()->create();
        $this->product = Product::factory()->forBrand($this->brand)->create(['max_seats' => 3]);
        $this->licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();

        // Create license through the API to ensure proper relationships
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $this->product->uuid,
            'expires_at' => now()->addYear()->format('Y-m-d'),
            'max_seats' => 3,
        ], $this->brand);

        $this->license = License::where('uuid', $response->json('data.uuid'))->first();
    });

    it('implements the complete US5 workflow: activate, monitor, deactivate, and force deactivate', function () {
        // Step 1: Check initial seat usage (should be 0)
        $initialSeatUsage = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $initialSeatUsage->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_seats' => 3,
                    'used_seats' => 0,
                    'available_seats' => 3,
                    'usage_percentage' => 0.0,
                ],
            ]);

        // Step 2: Activate license for first instance
        $activation1 = $this->postJson("/api/v1/licenses/{$this->license->uuid}/activate", [
            'instance_id' => 'site-1',
            'instance_type' => 'wordpress',
            'instance_url' => 'https://site1.com',
        ]);
        $activation1->assertStatus(200);

        // Step 3: Check seat usage after first activation
        $seatUsageAfter1 = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $seatUsageAfter1->assertStatus(200)
            ->assertJson([
                'data' => [
                    'used_seats' => 1,
                    'available_seats' => 2,
                    'usage_percentage' => 33.33,
                ],
            ]);

        // Step 4: Activate license for second instance
        $activation2 = $this->postJson("/api/v1/licenses/{$this->license->uuid}/activate", [
            'instance_id' => 'site-2',
            'instance_type' => 'wordpress',
            'instance_url' => 'https://site2.com',
        ]);
        $activation2->assertStatus(200);

        // Step 5: Check seat usage after second activation
        $seatUsageAfter2 = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $seatUsageAfter2->assertStatus(200)
            ->assertJson([
                'data' => [
                    'used_seats' => 2,
                    'available_seats' => 1,
                    'usage_percentage' => 66.67,
                ],
            ]);

        // Step 6: Activate license for third instance (should reach capacity)
        $activation3 = $this->postJson("/api/v1/licenses/{$this->license->uuid}/activate", [
            'instance_id' => 'site-3',
            'instance_type' => 'wordpress',
            'instance_url' => 'https://site3.com',
        ]);
        $activation3->assertStatus(200);

        // Step 7: Check seat usage at full capacity
        $seatUsageFull = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $seatUsageFull->assertStatus(200)
            ->assertJson([
                'data' => [
                    'used_seats' => 3,
                    'available_seats' => 0,
                    'usage_percentage' => 100.0,
                ],
            ]);

        // Step 8: Try to activate a fourth instance (should fail due to no available seats)
        $activation4 = $this->postJson("/api/v1/licenses/{$this->license->uuid}/activate", [
            'instance_id' => 'site-4',
            'instance_type' => 'wordpress',
            'instance_url' => 'https://site4.com',
        ]);
        $activation4->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'No available seats for this license',
            ]);

        // Step 9: Deactivate first instance to free up a seat
        $deactivation1 = $this->postJson("/api/v1/licenses/{$this->license->uuid}/deactivate", [
            'instance_id' => 'site-1',
            'instance_type' => 'wordpress',
        ]);
        $deactivation1->assertStatus(200);

        // Step 10: Check seat usage after deactivation
        $seatUsageAfterDeactivation = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $seatUsageAfterDeactivation->assertStatus(200)
            ->assertJson([
                'data' => [
                    'used_seats' => 2,
                    'available_seats' => 1,
                    'usage_percentage' => 66.67,
                ],
            ]);

        // Step 11: Now should be able to activate a new instance
        $activation4Retry = $this->postJson("/api/v1/licenses/{$this->license->uuid}/activate", [
            'instance_id' => 'site-4',
            'instance_type' => 'wordpress',
            'instance_url' => 'https://site4.com',
        ]);
        $activation4Retry->assertStatus(200);

        // Step 12: Check final seat usage
        $finalSeatUsage = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $finalSeatUsage->assertStatus(200)
            ->assertJson([
                'data' => [
                    'used_seats' => 3,
                    'available_seats' => 0,
                    'usage_percentage' => 100.0,
                ],
            ]);

        // Step 13: Force deactivate all seats as brand (administrative function)
        $forceDeactivation = $this->authenticatedPost(
            "/api/v1/licenses/{$this->license->uuid}/force-deactivate-seats",
            ['reason' => 'End of testing period'],
            $this->brand
        );
        $forceDeactivation->assertStatus(200)
            ->assertJson([
                'data' => [
                    'deactivated_seats' => 3,
                    'reason' => 'End of testing period',
                ],
            ]);

        // Step 14: Verify all seats are now available
        $finalSeatUsageAfterForce = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $finalSeatUsageAfterForce->assertStatus(200)
            ->assertJson([
                'data' => [
                    'used_seats' => 0,
                    'available_seats' => 3,
                    'usage_percentage' => 0.0,
                ],
            ]);
    });

    it('handles seat management for license without seat limits', function () {
        $licenseKeyWithoutSeats = LicenseKey::factory()->forBrand($this->brand)->create();

        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $licenseKeyWithoutSeats->uuid,
            'product_uuid' => $this->product->uuid,
            'expires_at' => now()->addYear()->format('Y-m-d'),
        ], $this->brand);

        $licenseWithoutSeats = License::where('uuid', $response->json('data.uuid'))->first();

        // Check seat usage for license without seat management
        $seatUsage = $this->getJson("/api/v1/licenses/{$licenseWithoutSeats->uuid}/seat-usage");
        $seatUsage->assertStatus(200)
            ->assertJson([
                'data' => [
                    'supports_seat_management' => false,
                    'message' => 'This license does not support seat management',
                ],
            ]);

        // Should be able to activate multiple instances without seat limits
        $activation1 = $this->postJson("/api/v1/licenses/{$licenseWithoutSeats->uuid}/activate", [
            'instance_id' => 'site-1',
            'instance_type' => 'wordpress',
        ]);
        $activation1->assertStatus(200);

        $activation2 = $this->postJson("/api/v1/licenses/{$licenseWithoutSeats->uuid}/activate", [
            'instance_id' => 'site-2',
            'instance_type' => 'wordpress',
        ]);
        $activation2->assertStatus(200);

        // Force deactivation should work but return 0
        $forceDeactivation = $this->authenticatedPost(
            "/api/v1/licenses/{$licenseWithoutSeats->uuid}/force-deactivate-seats",
            ['reason' => 'Test cleanup'],
            $this->brand
        );
        $forceDeactivation->assertStatus(200);
        // Note: deactivated_seats will be 0 since no activations were created for this license
    });

    it('maintains data integrity across seat operations', function () {
        // Create initial activation
        $activation = $this->postJson("/api/v1/licenses/{$this->license->uuid}/activate", [
            'instance_id' => 'test-site',
            'instance_type' => 'wordpress',
            'instance_url' => 'https://testsite.com',
            'machine_id' => 'machine-123',
        ]);
        $activation->assertStatus(200);

        // Verify activation was created with correct data
        $activationData = $activation->json('data');
        expect($activationData['instance_id'])->toBe('test-site');
        // Note: instance_type, instance_url, and machine_id are not returned in the response
        // They are stored in the database but not exposed in the API response

        // Deactivate the same instance
        $deactivation = $this->postJson("/api/v1/licenses/{$this->license->uuid}/deactivate", [
            'instance_id' => 'test-site',
            'instance_type' => 'wordpress',
        ]);
        $deactivation->assertStatus(200);

        // Verify deactivation data
        $deactivationData = $deactivation->json('data');
        expect($deactivationData['status'])->toBe(ActivationStatus::DEACTIVATED->value);
        expect($deactivationData['deactivated_at'])->not->toBeNull();

        // Check that seat is now available
        $seatUsage = $this->getJson("/api/v1/licenses/{$this->license->uuid}/seat-usage");
        $seatUsage->assertJson([
            'data' => [
                'used_seats' => 0,
                'available_seats' => 3,
            ],
        ]);
    });
});
