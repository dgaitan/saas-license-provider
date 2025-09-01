<?php

use App\Enums\LicenseStatus;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Tests\Feature\Api\V1\Brand\WithBrandAuthentication;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->product = Product::factory()->forBrand($this->brand)->create();
    $this->licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();
});

uses(WithBrandAuthentication::class);

describe('License API - US1: Brand can provision a license', function () {
    it('can create a license and associate it with a license key and product', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $this->product->uuid,
            'expires_at' => '2026-12-31',
            'max_seats' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'license_key_id',
                    'product_id',
                    'status',
                    'expires_at',
                    'max_seats',
                    'created_at',
                    'updated_at',
                    'license_key',
                    'product',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'License created successfully',
                'data' => [
                    'status' => LicenseStatus::VALID->value,
                    'expires_at' => '2026-12-31T00:00:00.000000Z',
                    'max_seats' => 5,
                ],
            ]);

        $this->assertDatabaseHas('licenses', [
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatus::VALID->value,
            'max_seats' => 5,
        ]);
    });

    it('can create a license without expiration date', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $this->product->uuid,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'expires_at' => null,
                    'max_seats' => null,
                ],
            ]);
    });

    it('can create a license without seat management', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $this->product->uuid,
            'expires_at' => '2026-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'max_seats' => null,
                ],
            ]);
    });

    it('validates license key UUID is required', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'product_uuid' => $this->product->uuid,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['license_key_uuid']);
    });

    it('validates license key UUID exists', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => 'non-existent-uuid',
            'product_uuid' => $this->product->uuid,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['license_key_uuid']);
    });

    it('validates product UUID is required', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_uuid']);
    });

    it('validates product UUID exists', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => 'non-existent-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_uuid']);
    });

    it('validates expiration date is in the future', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $this->product->uuid,
            'expires_at' => '2020-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    });

    it('validates max seats is a positive integer', function () {
        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $this->product->uuid,
            'max_seats' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_seats']);
    });

    it('can retrieve a license by UUID', function () {
        $license = License::factory()
            ->forLicenseKey($this->licenseKey)
            ->forProduct($this->product)
            ->create();

        $response = $this->authenticatedGet("/api/v1/licenses/{$license->uuid}");

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
                    'expires_at',
                    'max_seats',
                    'created_at',
                    'updated_at',
                    'license_key',
                    'product',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'License retrieved successfully',
                'data' => [
                    'uuid' => $license->uuid,
                    'status' => $license->status->value,
                ],
            ]);
    });

    it('returns 404 for non-existent license', function () {
        $response = $this->authenticatedGet('/api/v1/licenses/non-existent-uuid');

        $response->assertStatus(404);
    });

    it('includes license key and product relationships when retrieving license', function () {
        $license = License::factory()
            ->forLicenseKey($this->licenseKey)
            ->forProduct($this->product)
            ->create();

        $response = $this->authenticatedGet("/api/v1/licenses/{$license->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'license_key',
                    'product',
                ],
            ])
            ->assertJson([
                'data' => [
                    'license_key' => [
                        'uuid' => $this->licenseKey->uuid,
                    ],
                    'product' => [
                        'uuid' => $this->product->uuid,
                    ],
                ],
            ]);
    });

    it('enforces brand ownership for license key', function () {
        $otherBrand = Brand::factory()->create();
        $otherLicenseKey = LicenseKey::factory()->forBrand($otherBrand)->create();

        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $otherLicenseKey->uuid,
            'product_uuid' => $this->product->uuid,
        ]);

        $response->assertStatus(404);
    });

    it('enforces brand ownership for product', function () {
        $otherBrand = Brand::factory()->create();
        $otherProduct = Product::factory()->forBrand($otherBrand)->create();

        $response = $this->authenticatedPost('/api/v1/licenses', [
            'license_key_uuid' => $this->licenseKey->uuid,
            'product_uuid' => $otherProduct->uuid,
        ]);

        $response->assertStatus(404);
    });
});
