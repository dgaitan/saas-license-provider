<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\LicenseKey;
use App\Models\License;
use App\Models\Activation;
use App\Models\User;
use App\Enums\LicenseStatus;
use App\Enums\ActivationStatus;

describe('Model Relationships and Complex Scenarios', function () {
    it('can create a complete license ecosystem', function () {
        // Create a brand
        $brand = Brand::factory()->withName('WP Rocket')->create();

        // Create products for the brand
        $wpRocket = Product::factory()->withName('WP Rocket')->withSeats(5)->forBrand($brand)->create();
        $imagify = Product::factory()->withName('Imagify')->withSeats(3)->forBrand($brand)->create();

        // Create a license key for a customer
        $licenseKey = LicenseKey::factory()
            ->forBrand($brand)
            ->forCustomer('john@example.com')
            ->create();

        // Create licenses for the products
        $wpRocketLicense = License::factory()
            ->forLicenseKey($licenseKey)
            ->forProduct($wpRocket)
            ->withSeats(5)
            ->create();

        $imagifyLicense = License::factory()
            ->forLicenseKey($licenseKey)
            ->forProduct($imagify)
            ->withSeats(3)
            ->create();

        // Create activations
        Activation::factory()->active()->forLicense($wpRocketLicense)->forWordPressSite('https://site1.com')->create();
        Activation::factory()->active()->forLicense($wpRocketLicense)->forWordPressSite('https://site2.com')->create();
        Activation::factory()->active()->forLicense($imagifyLicense)->forWordPressSite('https://site1.com')->create();

        // Verify relationships
        expect($brand->products)->toHaveCount(2);
        expect($brand->licenseKeys)->toHaveCount(1);
        expect($licenseKey->licenses)->toHaveCount(2);
        expect($wpRocketLicense->activations)->toHaveCount(2);
        expect($imagifyLicense->activations)->toHaveCount(1);

        // Verify seat calculations
        expect($licenseKey->getTotalSeats())->toBe(8);
        expect($licenseKey->getUsedSeats())->toBe(3);
        expect($licenseKey->getRemainingSeats())->toBe(5);
    });

    it('can handle multi-brand scenario', function () {
        // Create two brands
        $wpRocketBrand = Brand::factory()->withName('WP Rocket')->create();
        $rankMathBrand = Brand::factory()->withName('RankMath')->create();

        // Create products for each brand
        $wpRocket = Product::factory()->withName('WP Rocket')->forBrand($wpRocketBrand)->create();
        $rankMath = Product::factory()->withName('RankMath')->forBrand($rankMathBrand)->create();
        $contentAI = Product::factory()->withName('Content AI')->forBrand($rankMathBrand)->create();

        // Create license keys for the same customer across brands
        $wpRocketKey = LicenseKey::factory()
            ->forBrand($wpRocketBrand)
            ->forCustomer('john@example.com')
            ->create();

        $rankMathKey = LicenseKey::factory()
            ->forBrand($rankMathBrand)
            ->forCustomer('john@example.com')
            ->create();

        // Create licenses
        $wpRocketLicense = License::factory()->forLicenseKey($wpRocketKey)->forProduct($wpRocket)->create();
        $rankMathLicense = License::factory()->forLicenseKey($rankMathKey)->forProduct($rankMath)->create();
        $contentAILicense = License::factory()->forLicenseKey($rankMathKey)->forProduct($contentAI)->create();

        // Verify brand isolation
        expect($wpRocketBrand->licenseKeys)->toHaveCount(1);
        expect($rankMathBrand->licenseKeys)->toHaveCount(1);
        expect($wpRocketKey->licenses)->toHaveCount(1);
        expect($rankMathKey->licenses)->toHaveCount(2);

        // Verify customer has access to both brands
        expect($wpRocketKey->customer_email)->toBe('john@example.com');
        expect($rankMathKey->customer_email)->toBe('john@example.com');
    });

    it('can handle license lifecycle management', function () {
        $brand = Brand::factory()->create();
        $product = Product::factory()->forBrand($brand)->create();
        $licenseKey = LicenseKey::factory()->forBrand($brand)->create();
        $license = License::factory()->forLicenseKey($licenseKey)->forProduct($product)->create();

        // Test suspension
        $license->suspend();
        expect($license->status)->toBe(LicenseStatus::SUSPENDED);
        expect($license->isValid())->toBeFalse();

        // Test resumption
        $license->resume();
        expect($license->status)->toBe(LicenseStatus::VALID);
        expect($license->isValid())->toBeTrue();

        // Test cancellation
        $license->cancel();
        expect($license->status)->toBe(LicenseStatus::CANCELLED);
        expect($license->isValid())->toBeFalse();

        // Test renewal
        $originalExpiry = $license->expires_at;
        $license->renew(365);
        expect($license->status)->toBe(LicenseStatus::VALID);
        expect($license->expires_at->timestamp)->toBeGreaterThan($originalExpiry->timestamp);
    });

    it('can handle seat management scenarios', function () {
        $brand = Brand::factory()->create();
        $productWithSeats = Product::factory()->withSeats(3)->forBrand($brand)->create();
        $productWithoutSeats = Product::factory()->withoutSeats()->forBrand($brand)->create();

        $licenseKey = LicenseKey::factory()->forBrand($brand)->create();
        $licenseWithSeats = License::factory()->withSeats(3)->forLicenseKey($licenseKey)->forProduct($productWithSeats)->create();
        $licenseWithoutSeats = License::factory()->withoutSeats()->forLicenseKey($licenseKey)->forProduct($productWithoutSeats)->create();

        // Test seat management
        expect($licenseWithSeats->supportsSeats())->toBeTrue();
        expect($licenseWithoutSeats->supportsSeats())->toBeFalse();
        expect($licenseWithSeats->getRemainingSeats())->toBe(3);
        expect($licenseWithoutSeats->getRemainingSeats())->toBe(0);

        // Test seat consumption
        Activation::factory()->active()->forLicense($licenseWithSeats)->create();
        Activation::factory()->active()->forLicense($licenseWithSeats)->create();

        expect($licenseWithSeats->getUsedSeats())->toBe(2);
        expect($licenseWithSeats->getRemainingSeats())->toBe(1);
        expect($licenseWithSeats->hasAvailableSeats())->toBeTrue();

        // Test seat exhaustion
        Activation::factory()->active()->forLicense($licenseWithSeats)->create();

        expect($licenseWithSeats->getUsedSeats())->toBe(3);
        expect($licenseWithSeats->getRemainingSeats())->toBe(0);
        expect($licenseWithSeats->hasAvailableSeats())->toBeFalse();
    });

    it('can handle activation management', function () {
        $brand = Brand::factory()->create();
        $product = Product::factory()->forBrand($brand)->create();
        $licenseKey = LicenseKey::factory()->forBrand($brand)->create();
        $license = License::factory()->forLicenseKey($licenseKey)->forProduct($product)->create();

        // Test activation
        $activation = Activation::factory()->forLicense($license)->create();
        expect($activation->isActive())->toBeTrue();

        // Test deactivation
        $activation->deactivate();
        expect($activation->isActive())->toBeFalse();
        expect($activation->deactivated_at)->not->toBeNull();

        // Test reactivation
        $activation->activate();
        expect($activation->isActive())->toBeTrue();
        expect($activation->deactivated_at)->toBeNull();
    });

    it('can handle user cross-brand license access', function () {
        // Create a user
        $user = User::factory()->create(['email' => 'john@example.com']);

        // Create brands and license keys
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();

        $licenseKey1 = LicenseKey::factory()->forBrand($brand1)->forCustomer($user->email)->create();
        $licenseKey2 = LicenseKey::factory()->forBrand($brand2)->forCustomer($user->email)->create();

        // Create products and licenses
        $product1 = Product::factory()->forBrand($brand1)->create();
        $product2 = Product::factory()->forBrand($brand2)->create();

        $license1 = License::factory()->forLicenseKey($licenseKey1)->forProduct($product1)->create();
        $license2 = License::factory()->forLicenseKey($licenseKey2)->forProduct($product2)->create();

        // Test user relationships
        expect($user->licenseKeys)->toHaveCount(2);
        expect($user->licenses)->toHaveCount(2);

        // Verify cross-brand access
        $userLicenses = $user->licenses()->get();
        expect($userLicenses->pluck('product.brand.name')->toArray())->toContain($brand1->name, $brand2->name);
    });

    it('can handle complex seat calculations across multiple licenses', function () {
        $brand = Brand::factory()->create();
        $licenseKey = LicenseKey::factory()->forBrand($brand)->create();

        // Create multiple products with different seat configurations
        $product1 = Product::factory()->withSeats(5)->forBrand($brand)->create();
        $product2 = Product::factory()->withSeats(3)->forBrand($brand)->create();
        $product3 = Product::factory()->withoutSeats()->forBrand($brand)->create();

        $license1 = License::factory()->withSeats(5)->forLicenseKey($licenseKey)->forProduct($product1)->create();
        $license2 = License::factory()->withSeats(3)->forLicenseKey($licenseKey)->forProduct($product2)->create();
        $license3 = License::factory()->withoutSeats()->forLicenseKey($licenseKey)->forProduct($product3)->create();

        // Create activations
        Activation::factory()->active()->forLicense($license1)->createMany([
            ['instance_id' => 'site1'],
            ['instance_id' => 'site2'],
        ]);

        Activation::factory()->active()->forLicense($license2)->create(['instance_id' => 'site3']);
        Activation::factory()->deactivated()->forLicense($license2)->create(['instance_id' => 'site4']);

        // Don't create activations for license3 since it doesn't support seat management

        // Test total calculations
        expect($licenseKey->getTotalSeats())->toBe(8); // 5 + 3 + 0
        expect($licenseKey->getUsedSeats())->toBe(3); // 2 + 1 + 0 (deactivated doesn't count)
        expect($licenseKey->getRemainingSeats())->toBe(5); // 8 - 3
    });
});
