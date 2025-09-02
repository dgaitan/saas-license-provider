<?php

namespace Tests\Feature\Api\V1\Brand;

use App\Models\Brand;
use App\Models\Product;
use App\Models\LicenseKey;
use App\Models\License;
use App\Models\Activation;
use App\Enums\LicenseStatus;
use App\Enums\ActivationStatus;

trait WithBrandAuthentication
{
    protected function getBrandHeaders(Brand $brand): array
    {
        return ['X-Tenant' => $brand->api_key];
    }

    protected function createTestBrand(): Brand
    {
        return Brand::factory()->create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'domain' => 'test-brand.com',
            'api_key' => 'brand_test_api_key_123',
            'is_active' => true,
        ]);
    }

    protected function createTestProduct(Brand $brand): Product
    {
        return Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'A test product for testing',
            'max_seats' => 5,
            'is_active' => true,
        ]);
    }

    protected function createTestLicenseKey(Brand $brand): LicenseKey
    {
        return LicenseKey::factory()->create([
            'brand_id' => $brand->id,
            'key' => 'test-license-key-123',
            'customer_email' => 'test@example.com',
            'is_active' => true,
        ]);
    }

    protected function createTestLicense(Product $product, LicenseKey $licenseKey): License
    {
        return License::factory()->create([
            'license_key_id' => $licenseKey->id,
            'product_id' => $product->id,
            'status' => LicenseStatus::VALID,
            'expires_at' => now()->addYear(),
            'max_seats' => 3,
        ]);
    }

    protected function createTestActivation(License $license): Activation
    {
        return Activation::factory()->create([
            'license_id' => $license->id,
            'instance_id' => 'test-instance-123',
            'instance_type' => 'wordpress',
            'instance_url' => 'https://test-site.com',
            'status' => ActivationStatus::ACTIVE,
            'activated_at' => now(),
        ]);
    }
}
