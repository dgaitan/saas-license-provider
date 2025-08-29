<?php

use App\Models\Brand;
use App\Models\LicenseKey;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
});

describe('License Key API - US1: Brand can provision a license', function () {
    it('can create a license key for a customer', function () {
        $response = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'test@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'brand_id',
                    'key',
                    'customer_email',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'License key created successfully',
                'data' => [
                    'customer_email' => 'test@example.com',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('license_keys', [
            'customer_email' => 'test@example.com',
            'is_active' => true,
        ]);
    });

    it('generates a unique license key', function () {
        $response1 = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'test1@example.com',
        ]);

        $response2 = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'test2@example.com',
        ]);

        $key1 = $response1->json('data.key');
        $key2 = $response2->json('data.key');

        expect($key1)->not->toBe($key2);
        expect(strlen($key1))->toBe(32);
        expect(strlen($key2))->toBe(32);
    });

    it('validates customer email is required', function () {
        $response = $this->postJson('/api/v1/license-keys', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    });

    it('validates customer email format', function () {
        $response = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    });

    it('validates customer email max length', function () {
        $response = $this->postJson('/api/v1/license-keys', [
            'customer_email' => str_repeat('a', 250) . '@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    });

    it('can retrieve a license key by UUID', function () {
        $licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();

        $response = $this->getJson("/api/v1/license-keys/{$licenseKey->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'brand_id',
                    'key',
                    'customer_email',
                    'is_active',
                    'created_at',
                    'updated_at',
                    'licenses',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'License key retrieved successfully',
                'data' => [
                    'uuid' => $licenseKey->uuid,
                    'customer_email' => $licenseKey->customer_email,
                    'is_active' => $licenseKey->is_active,
                ],
            ]);
    });

    it('returns 404 for non-existent license key', function () {
        $response = $this->getJson('/api/v1/license-keys/non-existent-uuid');

        $response->assertStatus(404);
    });

    it('includes licenses relationship when retrieving license key', function () {
        $licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();

        $response = $this->getJson("/api/v1/license-keys/{$licenseKey->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'licenses',
                ],
            ]);
    });
});
