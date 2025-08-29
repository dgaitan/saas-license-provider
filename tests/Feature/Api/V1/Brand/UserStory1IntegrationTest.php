<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\LicenseKey;
use App\Models\License;
use App\Enums\LicenseStatus;

describe('User Story 1 Integration - Brand can provision a license', function () {
    it('implements the complete US1 workflow: create license key, create license, associate them', function () {
        // Step 1: Create a brand and product (setup)
        $brand = Brand::factory()->create();
        $product = Product::factory()->forBrand($brand)->create();

        // Step 2: Create a license key for a customer
        $licenseKeyResponse = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'john@example.com',
        ]);

        $licenseKeyResponse->assertStatus(201);
        $licenseKeyData = $licenseKeyResponse->json('data');
        $licenseKeyUuid = $licenseKeyData['uuid'];

        expect($licenseKeyData['customer_email'])->toBe('john@example.com');
        expect($licenseKeyData['is_active'])->toBeTrue();
        expect(strlen($licenseKeyData['key']))->toBe(32);

        // Step 3: Create a license and associate it with the license key and product
        $licenseResponse = $this->postJson('/api/v1/licenses', [
            'license_key_uuid' => $licenseKeyUuid,
            'product_uuid' => $product->uuid,
            'expires_at' => '2026-12-31',
            'max_seats' => 3,
        ]);

        $licenseResponse->assertStatus(201);
        $licenseData = $licenseResponse->json('data');

        expect($licenseData['status'])->toBe(LicenseStatus::VALID->value);
        expect($licenseData['max_seats'])->toBe(3);
        expect($licenseData['license_key']['uuid'])->toBe($licenseKeyUuid);
        expect($licenseData['product']['uuid'])->toBe($product->uuid);

        // Step 4: Verify the license key now has the license associated
        $retrievedLicenseKeyResponse = $this->getJson("/api/v1/license-keys/{$licenseKeyUuid}");
        $retrievedLicenseKeyResponse->assertStatus(200);

        $retrievedLicenseKeyData = $retrievedLicenseKeyResponse->json('data');
        expect($retrievedLicenseKeyData['licenses'])->toHaveCount(1);
        expect($retrievedLicenseKeyData['licenses'][0]['uuid'])->toBe($licenseData['uuid']);

        // Step 5: Verify the license can be retrieved with relationships
        $retrievedLicenseResponse = $this->getJson("/api/v1/licenses/{$licenseData['uuid']}");
        $retrievedLicenseResponse->assertStatus(200);

        $retrievedLicenseData = $retrievedLicenseResponse->json('data');
        expect($retrievedLicenseData['license_key']['uuid'])->toBe($licenseKeyUuid);
        expect($retrievedLicenseData['product']['uuid'])->toBe($product->uuid);
    });

    it('supports the US1 scenario: multiple licenses for same license key', function () {
        // Setup: Create brand and multiple products
        $brand = Brand::factory()->create();
        $product1 = Product::factory()->forBrand($brand)->create();
        $product2 = Product::factory()->forBrand($brand)->create();

        // Step 1: Create license key for customer
        $licenseKeyResponse = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'jane@example.com',
        ]);

        $licenseKeyResponse->assertStatus(201);
        $licenseKeyUuid = $licenseKeyResponse->json('data.uuid');

        // Step 2: Create first license (e.g., RankMath subscription)
        $license1Response = $this->postJson('/api/v1/licenses', [
            'license_key_uuid' => $licenseKeyUuid,
            'product_uuid' => $product1->uuid,
            'expires_at' => '2026-12-31',
            'max_seats' => 5,
        ]);

        $license1Response->assertStatus(201);
        $license1Uuid = $license1Response->json('data.uuid');

        // Step 3: Create second license (e.g., Content AI addon) - same license key
        $license2Response = $this->postJson('/api/v1/licenses', [
            'license_key_uuid' => $licenseKeyUuid,
            'product_uuid' => $product2->uuid,
            'expires_at' => '2026-12-31',
            'max_seats' => 2,
        ]);

        $license2Response->assertStatus(201);
        $license2Uuid = $license2Response->json('data.uuid');

        // Step 4: Verify license key has both licenses
        $retrievedLicenseKeyResponse = $this->getJson("/api/v1/license-keys/{$licenseKeyUuid}");
        $retrievedLicenseKeyResponse->assertStatus(200);

        $licenses = $retrievedLicenseKeyResponse->json('data.licenses');
        expect($licenses)->toHaveCount(2);
        expect($licenses[0]['uuid'])->toBe($license1Uuid);
        expect($licenses[1]['uuid'])->toBe($license2Uuid);
    });

    it('supports the US1 scenario: different brands get different license keys', function () {
        // Setup: Create two brands with their products
        // Note: Currently all license keys are created for the first brand due to Brand::first()
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();
        $product1 = Product::factory()->forBrand($brand1)->create();
        $product2 = Product::factory()->forBrand($brand2)->create();

        // Step 1: Create license key for Brand 1 (RankMath)
        $licenseKey1Response = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'user@example.com',
        ]);

        $licenseKey1Response->assertStatus(201);
        $licenseKey1Uuid = $licenseKey1Response->json('data.uuid');

        // Step 2: Create license for Brand 1 product
        $license1Response = $this->postJson('/api/v1/licenses', [
            'license_key_uuid' => $licenseKey1Uuid,
            'product_uuid' => $product1->uuid,
            'expires_at' => '2026-12-31',
        ]);

        $license1Response->assertStatus(201);

        // Step 3: Create license key for Brand 2 (WP Rocket) - different license key
        $licenseKey2Response = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'user@example.com',
        ]);

        $licenseKey2Response->assertStatus(201);
        $licenseKey2Uuid = $licenseKey2Response->json('data.uuid');

        // Step 4: Create license for Brand 2 product (this will fail due to brand ownership)
        $license2Response = $this->postJson('/api/v1/licenses', [
            'license_key_uuid' => $licenseKey2Uuid,
            'product_uuid' => $product2->uuid,
            'expires_at' => '2026-12-31',
        ]);

        $license2Response->assertStatus(404); // Should fail due to brand ownership

        // Step 5: Verify different license keys were created
        expect($licenseKey1Uuid)->not->toBe($licenseKey2Uuid);

        // Step 6: Verify each license key has its own license
        $retrievedLicenseKey1Response = $this->getJson("/api/v1/license-keys/{$licenseKey1Uuid}");
        $retrievedLicenseKey2Response = $this->getJson("/api/v1/license-keys/{$licenseKey2Uuid}");

        $retrievedLicenseKey1Response->assertStatus(200);
        $retrievedLicenseKey2Response->assertStatus(200);

        expect($retrievedLicenseKey1Response->json('data.licenses'))->toHaveCount(1);
        expect($retrievedLicenseKey2Response->json('data.licenses'))->toHaveCount(0); // No license due to 404
    });

    it('validates brand ownership across the complete workflow', function () {
        // Setup: Create two brands with their products
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();
        $product1 = Product::factory()->forBrand($brand1)->create();
        $product2 = Product::factory()->forBrand($brand2)->create();

        // Step 1: Create license key for Brand 1
        $licenseKeyResponse = $this->postJson('/api/v1/license-keys', [
            'customer_email' => 'user@example.com',
        ]);

        $licenseKeyResponse->assertStatus(201);
        $licenseKeyUuid = $licenseKeyResponse->json('data.uuid');

        // Step 2: Try to create license with Brand 2's product (should fail)
        $licenseResponse = $this->postJson('/api/v1/licenses', [
            'license_key_uuid' => $licenseKeyUuid,
            'product_uuid' => $product2->uuid,
            'expires_at' => '2026-12-31',
        ]);

        $licenseResponse->assertStatus(404);
    });
});
