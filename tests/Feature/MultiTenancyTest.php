<?php

use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Services\MultiTenancyService;

describe('Multi-Tenancy - Brand and Product Isolation', function () {
    beforeEach(function () {
        $this->multiTenancyService = app(MultiTenancyService::class);

        // Create multiple brands
        $this->rankMathBrand = Brand::factory()->create([
            'name' => 'RankMath',
            'slug' => 'rankmath',
            'api_key' => 'brand_rankmath_test_key_123456789',
        ]);

        $this->wpRocketBrand = Brand::factory()->create([
            'name' => 'WP Rocket',
            'slug' => 'wp-rocket',
            'api_key' => 'brand_wprocket_test_key_123456789',
        ]);

        // Create products for each brand
        $this->rankMathProduct = Product::factory()->forBrand($this->rankMathBrand)->create([
            'name' => 'RankMath Pro',
            'slug' => 'rankmath-pro',
        ]);

        $this->contentAiProduct = Product::factory()->forBrand($this->rankMathBrand)->create([
            'name' => 'Content AI',
            'slug' => 'content-ai',
        ]);

        $this->wpRocketProduct = Product::factory()->forBrand($this->wpRocketBrand)->create([
            'name' => 'WP Rocket',
            'slug' => 'wp-rocket',
        ]);

        // Create license keys for each brand
        $this->rankMathLicenseKey = LicenseKey::factory()->forBrand($this->rankMathBrand)->create([
            'customer_email' => 'user@example.com',
        ]);

        $this->wpRocketLicenseKey = LicenseKey::factory()->forBrand($this->wpRocketBrand)->create([
            'customer_email' => 'user@example.com',
        ]);

        // Create licenses
        $this->rankMathLicense = License::factory()
            ->forLicenseKey($this->rankMathLicenseKey)
            ->forProduct($this->rankMathProduct)
            ->create();

        $this->contentAiLicense = License::factory()
            ->forLicenseKey($this->rankMathLicenseKey)
            ->forProduct($this->contentAiProduct)
            ->create();

        $this->wpRocketLicense = License::factory()
            ->forLicenseKey($this->wpRocketLicenseKey)
            ->forProduct($this->wpRocketProduct)
            ->create();
    });

    it('ensures brand isolation at the model level', function () {
        // Products should only belong to their respective brands
        expect($this->rankMathProduct->brand_id)->toBe($this->rankMathBrand->id);
        expect($this->wpRocketProduct->brand_id)->toBe($this->wpRocketBrand->id);

        // License keys should only belong to their respective brands
        expect($this->rankMathLicenseKey->brand_id)->toBe($this->rankMathBrand->id);
        expect($this->wpRocketLicenseKey->brand_id)->toBe($this->wpRocketBrand->id);

        // Licenses should be isolated through their relationships
        expect($this->rankMathLicense->getBrandId())->toBe($this->rankMathBrand->id);
        expect($this->wpRocketLicense->getBrandId())->toBe($this->wpRocketBrand->id);
    });

    it('enforces brand ownership validation', function () {
        // Validate license key ownership
        expect($this->multiTenancyService->validateLicenseKeyBrandOwnership(
            $this->rankMathLicenseKey->uuid,
            $this->rankMathBrand
        ))->toBeTrue();

        expect($this->multiTenancyService->validateLicenseKeyBrandOwnership(
            $this->rankMathLicenseKey->uuid,
            $this->wpRocketBrand
        ))->toBeFalse();

        // Validate product ownership
        expect($this->multiTenancyService->validateProductBrandOwnership(
            $this->rankMathProduct->uuid,
            $this->rankMathBrand
        ))->toBeTrue();

        expect($this->multiTenancyService->validateProductBrandOwnership(
            $this->rankMathProduct->uuid,
            $this->wpRocketBrand
        ))->toBeFalse();

        // Validate license ownership
        expect($this->multiTenancyService->validateLicenseBrandOwnership(
            $this->rankMathLicense->uuid,
            $this->rankMathBrand
        ))->toBeTrue();

        expect($this->multiTenancyService->validateLicenseBrandOwnership(
            $this->rankMathLicense->uuid,
            $this->wpRocketBrand
        ))->toBeFalse();
    });

    it('provides brand-scoped queries', function () {
        // Get products for each brand
        $rankMathProducts = $this->multiTenancyService->getProductsForBrand($this->rankMathBrand);
        $wpRocketProducts = $this->multiTenancyService->getProductsForBrand($this->wpRocketBrand);

        expect($rankMathProducts)->toHaveCount(2); // RankMath Pro + Content AI
        expect($wpRocketProducts)->toHaveCount(1); // WP Rocket only

        expect($rankMathProducts->pluck('name')->toArray())->toContain('RankMath Pro');
        expect($rankMathProducts->pluck('name')->toArray())->toContain('Content AI');
        expect($wpRocketProducts->pluck('name')->toArray())->toContain('WP Rocket');

        // Get license keys for each brand
        $rankMathLicenseKeys = $this->multiTenancyService->getLicenseKeysForBrand($this->rankMathBrand);
        $wpRocketLicenseKeys = $this->multiTenancyService->getLicenseKeysForBrand($this->wpRocketBrand);

        expect($rankMathLicenseKeys)->toHaveCount(1);
        expect($wpRocketLicenseKeys)->toHaveCount(1);

        // Get licenses for each brand
        $rankMathLicenses = $this->multiTenancyService->getLicensesForBrand($this->rankMathBrand);
        $wpRocketLicenses = $this->multiTenancyService->getLicensesForBrand($this->wpRocketBrand);

        expect($rankMathLicenses)->toHaveCount(2); // RankMath + Content AI licenses
        expect($wpRocketLicenses)->toHaveCount(1); // WP Rocket license only
    });

    it('supports cross-brand customer queries', function () {
        $customerEmail = 'user@example.com';

        // Get all license keys for customer across all brands
        $allLicenseKeys = $this->multiTenancyService->getLicenseKeysForCustomer($customerEmail);
        expect($allLicenseKeys)->toHaveCount(2); // One for each brand

        // Get license keys for customer within specific brands
        $rankMathLicenseKeys = $this->multiTenancyService->getLicenseKeysForCustomerInBrand(
            $customerEmail,
            $this->rankMathBrand
        );
        $wpRocketLicenseKeys = $this->multiTenancyService->getLicenseKeysForCustomerInBrand(
            $customerEmail,
            $this->wpRocketBrand
        );

        expect($rankMathLicenseKeys)->toHaveCount(1);
        expect($wpRocketLicenseKeys)->toHaveCount(1);

        // Get all licenses for customer across all brands
        $allLicenses = $this->multiTenancyService->getLicensesForCustomer($customerEmail);
        expect($allLicenses)->toHaveCount(3); // RankMath + Content AI + WP Rocket

        // Get licenses for customer within specific brands
        $rankMathLicenses = $this->multiTenancyService->getLicensesForCustomerInBrand(
            $customerEmail,
            $this->rankMathBrand
        );
        $wpRocketLicenses = $this->multiTenancyService->getLicensesForCustomerInBrand(
            $customerEmail,
            $this->wpRocketBrand
        );

        expect($rankMathLicenses)->toHaveCount(2); // RankMath + Content AI
        expect($wpRocketLicenses)->toHaveCount(1); // WP Rocket only
    });

    it('provides brand and customer statistics', function () {
        // Brand statistics
        $rankMathStats = $this->multiTenancyService->getBrandStatistics($this->rankMathBrand);
        $wpRocketStats = $this->multiTenancyService->getBrandStatistics($this->wpRocketBrand);

        expect($rankMathStats['products_count'])->toBe(2);
        expect($rankMathStats['license_keys_count'])->toBe(1);
        expect($rankMathStats['licenses_count'])->toBe(2);

        expect($wpRocketStats['products_count'])->toBe(1);
        expect($wpRocketStats['license_keys_count'])->toBe(1);
        expect($wpRocketStats['licenses_count'])->toBe(1);

        // Customer statistics
        $customerStats = $this->multiTenancyService->getCustomerStatistics('user@example.com');
        expect($customerStats['brands_count'])->toBe(2);
        expect($customerStats['license_keys_count'])->toBe(2);
        expect($customerStats['licenses_count'])->toBe(3);
    });

    it('validates product access across brands', function () {
        $customerEmail = 'user@example.com';

        // Customer should have access to RankMath Pro across any brand
        expect($this->multiTenancyService->customerHasProductAccess(
            $customerEmail,
            'rankmath-pro'
        ))->toBeTrue();

        // Customer should have access to Content AI across any brand
        expect($this->multiTenancyService->customerHasProductAccess(
            $customerEmail,
            'content-ai'
        ))->toBeTrue();

        // Customer should have access to WP Rocket across any brand
        expect($this->multiTenancyService->customerHasProductAccess(
            $customerEmail,
            'wp-rocket'
        ))->toBeTrue();

        // Customer should have access to RankMath Pro within RankMath brand
        expect($this->multiTenancyService->customerHasProductAccessInBrand(
            $customerEmail,
            'rankmath-pro',
            $this->rankMathBrand
        ))->toBeTrue();

        // Customer should NOT have access to RankMath Pro within WP Rocket brand
        expect($this->multiTenancyService->customerHasProductAccessInBrand(
            $customerEmail,
            'rankmath-pro',
            $this->wpRocketBrand
        ))->toBeFalse();
    });

    it('supports model scopes for multi-tenancy', function () {
        // Test Product scopes
        $rankMathProducts = Product::forBrand($this->rankMathBrand)->get();
        $wpRocketProducts = Product::forBrand($this->wpRocketBrand)->get();

        expect($rankMathProducts)->toHaveCount(2);
        expect($wpRocketProducts)->toHaveCount(1);

        // Test LicenseKey scopes
        $rankMathLicenseKeys = LicenseKey::forBrand($this->rankMathBrand)->get();
        $wpRocketLicenseKeys = LicenseKey::forBrand($this->wpRocketBrand)->get();

        expect($rankMathLicenseKeys)->toHaveCount(1);
        expect($wpRocketLicenseKeys)->toHaveCount(1);

        // Test License scopes
        $rankMathLicenses = License::forBrand($this->rankMathBrand)->get();
        $wpRocketLicenses = License::forBrand($this->wpRocketBrand)->get();

        expect($rankMathLicenses)->toHaveCount(2);
        expect($wpRocketLicenses)->toHaveCount(1);
    });

    it('enforces brand ownership in model relationships', function () {
        // License key should belong to its brand
        expect($this->rankMathLicenseKey->belongsToBrand($this->rankMathBrand))->toBeTrue();
        expect($this->rankMathLicenseKey->belongsToBrand($this->wpRocketBrand))->toBeFalse();

        // Product should belong to its brand
        expect($this->rankMathProduct->belongsToBrand($this->rankMathBrand))->toBeTrue();
        expect($this->rankMathProduct->belongsToBrand($this->wpRocketBrand))->toBeFalse();

        // License should belong to its brand through license key
        expect($this->rankMathLicense->belongsToBrand($this->rankMathBrand))->toBeTrue();
        expect($this->rankMathLicense->belongsToBrand($this->wpRocketBrand))->toBeFalse();

        // All should belong to active brands
        expect($this->rankMathLicenseKey->belongsToActiveBrand())->toBeTrue();
        expect($this->rankMathProduct->belongsToActiveBrand())->toBeTrue();
        expect($this->rankMathLicense->belongsToActiveBrand())->toBeTrue();
    });

    it('supports cross-brand customer brand discovery', function () {
        $customerBrands = $this->multiTenancyService->getCustomerBrands('user@example.com');

        expect($customerBrands)->toHaveCount(2);
        expect($customerBrands->pluck('name')->toArray())->toContain('RankMath');
        expect($customerBrands->pluck('name')->toArray())->toContain('WP Rocket');
    });

    it('maintains data integrity across brand boundaries', function () {
        // Create a new license key for a different customer in RankMath brand
        $newLicenseKey = LicenseKey::factory()->forBrand($this->rankMathBrand)->create([
            'customer_email' => 'another@example.com',
        ]);

        // This should not affect the original customer's data
        $originalCustomerLicenseKeys = $this->multiTenancyService->getLicenseKeysForCustomerInBrand(
            'user@example.com',
            $this->rankMathBrand
        );

        expect($originalCustomerLicenseKeys)->toHaveCount(1);
        expect($originalCustomerLicenseKeys->first()->customer_email)->toBe('user@example.com');

        // The new customer should have their own license key
        $newCustomerLicenseKeys = $this->multiTenancyService->getLicenseKeysForCustomerInBrand(
            'another@example.com',
            $this->rankMathBrand
        );

        expect($newCustomerLicenseKeys)->toHaveCount(1);
        expect($newCustomerLicenseKeys->first()->customer_email)->toBe('another@example.com');
    });
});
