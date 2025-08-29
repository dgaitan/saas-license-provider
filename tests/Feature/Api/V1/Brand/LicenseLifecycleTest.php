<?php

use App\Enums\LicenseStatus;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->product = Product::factory()->for($this->brand)->create();
    $this->licenseKey = LicenseKey::factory()->for($this->brand)->create();
    $this->license = License::factory()
        ->for($this->licenseKey)
        ->for($this->product)
        ->create([
            'status' => LicenseStatus::VALID,
            'expires_at' => now()->addDays(30),
            'max_seats' => 5,
        ]);
});

describe('License Lifecycle API - US2: Brand can change license lifecycle', function () {
    it('can renew a license by extending expiration date', function () {
        $originalExpiresAt = $this->license->expires_at;

        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/renew", [
            'days' => 365,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'license_key_id',
                    'product_id',
                    'status',
                    'status_label',
                    'expires_at',
                    'max_seats',
                    'product',
                    'license_key',
                    'created_at',
                    'updated_at',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();
        expect($response->json('message'))->toBe('License renewed successfully');
        expect($response->json('data.status'))->toBe(LicenseStatus::VALID->value);

        // Check that expiration date was extended
        $newExpiresAt = $response->json('data.expires_at');
        expect($newExpiresAt)->not->toBe($originalExpiresAt->toISOString());

        // Verify the license was actually updated in database
        $this->license->refresh();
        expect(abs($this->license->expires_at->diffInDays($originalExpiresAt, false)))->toBeGreaterThan(300); // Should be around 365 days
    });

    it('can renew a license with default days (365)', function () {
        $originalExpiresAt = $this->license->expires_at;

        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/renew");

        $response->assertStatus(200);
        expect($response->json('success'))->toBeTrue();

        // Verify the license was extended by approximately 365 days
        $this->license->refresh();
        expect(abs($this->license->expires_at->diffInDays($originalExpiresAt, false)))->toBeGreaterThan(300);
    });

    it('validates days parameter for renewal', function () {
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/renew", [
            'days' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['days']);

        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/renew", [
            'days' => 4000, // More than 10 years
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['days']);
    });

    it('can suspend a license', function () {
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/suspend");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'status',
                    'status_label',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();
        expect($response->json('message'))->toBe('License suspended successfully');
        expect($response->json('data.status'))->toBe(LicenseStatus::SUSPENDED->value);
        expect($response->json('data.status_label'))->toBe(LicenseStatus::SUSPENDED->label());

        // Verify the license was actually updated in database
        $this->license->refresh();
        expect($this->license->status)->toBe(LicenseStatus::SUSPENDED);
    });

    it('can resume a suspended license', function () {
        // First suspend the license
        $this->license->update(['status' => LicenseStatus::SUSPENDED]);

        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/resume");

        $response->assertStatus(200);
        expect($response->json('success'))->toBeTrue();
        expect($response->json('message'))->toBe('License resumed successfully');
        expect($response->json('data.status'))->toBe(LicenseStatus::VALID->value);

        // Verify the license was actually updated in database
        $this->license->refresh();
        expect($this->license->status)->toBe(LicenseStatus::VALID);
    });

    it('can cancel a license', function () {
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/cancel");

        $response->assertStatus(200);
        expect($response->json('success'))->toBeTrue();
        expect($response->json('message'))->toBe('License cancelled successfully');
        expect($response->json('data.status'))->toBe(LicenseStatus::CANCELLED->value);

        // Verify the license was actually updated in database
        $this->license->refresh();
        expect($this->license->status)->toBe(LicenseStatus::CANCELLED);
    });

    it('returns 404 for non-existent license', function () {
        $nonExistentUuid = '00000000-0000-0000-0000-000000000000';

        $response = $this->patchJson("/api/v1/licenses/{$nonExistentUuid}/renew");
        $response->assertStatus(404);

        $response = $this->patchJson("/api/v1/licenses/{$nonExistentUuid}/suspend");
        $response->assertStatus(404);

        $response = $this->patchJson("/api/v1/licenses/{$nonExistentUuid}/resume");
        $response->assertStatus(404);

        $response = $this->patchJson("/api/v1/licenses/{$nonExistentUuid}/cancel");
        $response->assertStatus(404);
    });

    it('enforces brand ownership for license operations', function () {
        // Create a different brand and license
        $otherBrand = Brand::factory()->create();
        $otherProduct = Product::factory()->for($otherBrand)->create();
        $otherLicenseKey = LicenseKey::factory()->for($otherBrand)->create();
        $otherLicense = License::factory()
            ->for($otherLicenseKey)
            ->for($otherProduct)
            ->create();

        // Try to operate on license from different brand
        $response = $this->patchJson("/api/v1/licenses/{$otherLicense->uuid}/renew");
        $response->assertStatus(404);
        expect($response->json('message'))->toContain('not found');

        $response = $this->patchJson("/api/v1/licenses/{$otherLicense->uuid}/suspend");
        $response->assertStatus(404);

        $response = $this->patchJson("/api/v1/licenses/{$otherLicense->uuid}/resume");
        $response->assertStatus(404);

        $response = $this->patchJson("/api/v1/licenses/{$otherLicense->uuid}/cancel");
        $response->assertStatus(404);
    });

    it('can perform complete lifecycle operations', function () {
        // 1. Start with valid license
        expect($this->license->status)->toBe(LicenseStatus::VALID);

        // 2. Suspend the license
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/suspend");
        $response->assertStatus(200);
        $this->license->refresh();
        expect($this->license->status)->toBe(LicenseStatus::SUSPENDED);

        // 3. Resume the license
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/resume");
        $response->assertStatus(200);
        $this->license->refresh();
        expect($this->license->status)->toBe(LicenseStatus::VALID);

        // 4. Renew the license
        $originalExpiresAt = $this->license->expires_at;
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/renew", ['days' => 180]);
        $response->assertStatus(200);
        $this->license->refresh();
        expect(abs($this->license->expires_at->diffInDays($originalExpiresAt, false)))->toBeGreaterThan(150);

        // 5. Cancel the license
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/cancel");
        $response->assertStatus(200);
        $this->license->refresh();
        expect($this->license->status)->toBe(LicenseStatus::CANCELLED);
    });

    it('includes license key and product relationships in responses', function () {
        $response = $this->patchJson("/api/v1/licenses/{$this->license->uuid}/renew");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'license_key' => [
                        'id',
                        'uuid',
                        'key',
                        'customer_email',
                        'is_active',
                    ],
                    'product' => [
                        'id',
                        'uuid',
                        'name',
                        'slug',
                        'description',
                        'max_seats',
                        'is_active',
                    ],
                ],
            ]);

        expect($response->json('data.license_key.uuid'))->toBe($this->licenseKey->uuid);
        expect($response->json('data.product.uuid'))->toBe($this->product->uuid);
    });
});
