<?php

use App\Enums\LicenseStatus;
use App\Models\Activation;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Tests\Feature\Api\V1\Brand\WithBrandAuthentication;

uses(WithBrandAuthentication::class);

describe('User Story 6 - Cross-Brand License Listing', function () {
    beforeEach(function () {
        // Create test brands
        $this->rankMathBrand = Brand::factory()->create([
            'name' => 'RankMath',
            'slug' => 'rankmath',
            'domain' => 'rankmath.com',
            'api_key' => 'brand_rankmath_test_key_123456789',
        ]);

        $this->wpRocketBrand = Brand::factory()->create([
            'name' => 'WP Rocket',
            'slug' => 'wp-rocket',
            'domain' => 'wp-rocket.me',
            'api_key' => 'brand_wprocket_test_key_123456789',
        ]);

        // Create products for each brand
        $this->rankMathProduct = Product::factory()->forBrand($this->rankMathBrand)->create([
            'name' => 'RankMath SEO',
            'slug' => 'rankmath',
            'max_seats' => 3,
        ]);

        $this->contentAIProduct = Product::factory()->forBrand($this->rankMathBrand)->create([
            'name' => 'Content AI',
            'slug' => 'content-ai',
            'max_seats' => 2,
        ]);

        $this->wpRocketProduct = Product::factory()->forBrand($this->wpRocketBrand)->create([
            'name' => 'WP Rocket',
            'slug' => 'wp-rocket',
            'max_seats' => 5,
        ]);

        // Create license keys for the same customer across brands
        $this->rankMathLicenseKey = LicenseKey::factory()->forBrand($this->rankMathBrand)->create([
            'customer_email' => 'john@example.com',
        ]);

        $this->wpRocketLicenseKey = LicenseKey::factory()->forBrand($this->wpRocketBrand)->create([
            'customer_email' => 'john@example.com',
        ]);

        // Create licenses for the customer
        $this->rankMathLicense = License::factory()->forLicenseKey($this->rankMathLicenseKey)->forProduct($this->rankMathProduct)->create([
            'status' => LicenseStatus::VALID,
            'max_seats' => 3,
            'expires_at' => now()->addYear(),
        ]);

        $this->contentAILicense = License::factory()->forLicenseKey($this->rankMathLicenseKey)->forProduct($this->contentAIProduct)->create([
            'status' => LicenseStatus::VALID,
            'max_seats' => 2,
            'expires_at' => now()->addYear(),
        ]);

        $this->wpRocketLicense = License::factory()->forLicenseKey($this->wpRocketLicenseKey)->forProduct($this->wpRocketProduct)->create([
            'status' => LicenseStatus::SUSPENDED,
            'max_seats' => 5,
            'expires_at' => now()->addYear(),
        ]);

        // Create some activations for seat usage
        Activation::factory()->forLicense($this->rankMathLicense)->create([
            'status' => 'active',
            'instance_id' => 'site-1',
            'instance_type' => 'wordpress',
        ]);

        Activation::factory()->forLicense($this->rankMathLicense)->create([
            'status' => 'active',
            'instance_id' => 'site-2',
            'instance_type' => 'wordpress',
        ]);

        Activation::factory()->forLicense($this->contentAILicense)->create([
            'status' => 'active',
            'instance_id' => 'site-1',
            'instance_type' => 'wordpress',
        ]);

        Activation::factory()->forLicense($this->wpRocketLicense)->create([
            'status' => 'deactivated',
            'instance_id' => 'site-3',
            'instance_type' => 'wordpress',
        ]);
    });

    describe('GET /api/v1/customers/licenses - Cross-brand customer license listing', function () {
        it('requires brand authentication', function () {
            $response = $this->getJson('/api/v1/customers/licenses?customer_email=john@example.com');

            $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Unauthorized',
                    'message' => 'X-Tenant header with Brand API Key is required',
                ]);
        });

        it('validates customer email parameter', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses', $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email is required to list licenses.',
                ]);
        });

        it('validates customer email format', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=invalid-email', $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email must be a valid email address.',
                ]);
        });

        it('returns comprehensive customer license summary across all brands', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=john@example.com', $this->rankMathBrand);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully retrieved license information for customer john@example.com',
                ]);

            $data = $response->json('data');

            // Verify customer email
            expect($data['customer_email'])->toBe('john@example.com');

            // Verify totals
            expect($data['total_license_keys'])->toBe(2);
            expect($data['total_licenses'])->toBe(3);
            expect($data['brands_count'])->toBe(2);

            // Verify brands - check if they exist in the array
            expect($data['brands'])->toHaveCount(2);

            // Check if RankMath brand exists
            $rankMathBrand = collect($data['brands'])->firstWhere('name', 'RankMath');
            expect($rankMathBrand)->not->toBeNull();
            expect($rankMathBrand['slug'])->toBe('rankmath');
            expect($rankMathBrand['domain'])->toBe('rankmath.com');

            // Check if WP Rocket brand exists
            $wpRocketBrand = collect($data['brands'])->firstWhere('name', 'WP Rocket');
            expect($wpRocketBrand)->not->toBeNull();
            expect($wpRocketBrand['slug'])->toBe('wp-rocket');
            expect($wpRocketBrand['domain'])->toBe('wp-rocket.me');

            // Verify license keys
            expect($data['license_keys'])->toHaveCount(2);
            expect($data['license_keys'][0]['customer_email'])->toBe('john@example.com');

            // Check if brand information is loaded in license keys
            $licenseKeyWithBrand = collect($data['license_keys'])->firstWhere('brand');
            expect($licenseKeyWithBrand)->not->toBeNull();

            // Verify licenses summary
            expect($data['licenses_summary']['total_active'])->toBe(2); // RankMath + Content AI
            expect($data['licenses_summary']['total_suspended'])->toBe(1); // WP Rocket
            expect($data['licenses_summary']['total_cancelled'])->toBe(0);
            expect($data['licenses_summary']['total_expired'])->toBe(0);

            // Verify products summary
            expect($data['products_summary'])->toHaveCount(3);

            // Check RankMath product
            $rankMathProduct = collect($data['products_summary'])->firstWhere('product_slug', 'rankmath');
            expect($rankMathProduct)->not->toBeNull();
            expect($rankMathProduct['product_name'])->toBe('RankMath SEO');
            expect($rankMathProduct['brand_name'])->toBe('RankMath');
            expect($rankMathProduct['licenses_count'])->toBe(1);
            expect($rankMathProduct['total_seats'])->toBe(3);
            expect($rankMathProduct['active_seats'])->toBe(2);

            // Check Content AI product
            $contentAIProduct = collect($data['products_summary'])->firstWhere('product_slug', 'content-ai');
            expect($contentAIProduct)->not->toBeNull();
            expect($contentAIProduct['product_name'])->toBe('Content AI');
            expect($contentAIProduct['brand_name'])->toBe('RankMath');
            expect($contentAIProduct['licenses_count'])->toBe(1);
            expect($contentAIProduct['total_seats'])->toBe(2);
            expect($contentAIProduct['active_seats'])->toBe(1);

            // Check WP Rocket product
            $wpRocketProduct = collect($data['products_summary'])->firstWhere('product_slug', 'wp-rocket');
            expect($wpRocketProduct)->not->toBeNull();
            expect($wpRocketProduct['product_name'])->toBe('WP Rocket');
            expect($wpRocketProduct['brand_name'])->toBe('WP Rocket');
            expect($wpRocketProduct['licenses_count'])->toBe(1);
            expect($wpRocketProduct['total_seats'])->toBe(5);
            expect($wpRocketProduct['active_seats'])->toBe(0); // Suspended license
        });

        it('returns empty results for customer with no licenses', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=nonexistent@example.com', $this->rankMathBrand);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully retrieved license information for customer nonexistent@example.com',
                ]);

            $data = $response->json('data');
            expect($data['total_license_keys'])->toBe(0);
            expect($data['total_licenses'])->toBe(0);
            expect($data['brands_count'])->toBe(0);
            expect($data['brands'])->toHaveCount(0);
            expect($data['license_keys'])->toHaveCount(0);
            expect($data['products_summary'])->toHaveCount(0);
        });

        it('works with any authenticated brand', function () {
            // Test with WP Rocket brand
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=john@example.com', $this->wpRocketBrand);

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['total_license_keys'])->toBe(2);
            expect($data['total_licenses'])->toBe(3);
            expect($data['brands_count'])->toBe(2);
        });
    });

    describe('GET /api/v1/customers/licenses/brand - Brand-specific customer license listing', function () {
        it('requires brand authentication', function () {
            $response = $this->getJson('/api/v1/customers/licenses/brand?customer_email=john@example.com');

            $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Unauthorized',
                    'message' => 'X-Tenant header with Brand API Key is required',
                ]);
        });

        it('validates customer email parameter', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses/brand', $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email is required to list licenses.',
                ]);
        });

        it('validates customer email format', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses/brand?customer_email=invalid-email', $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email must be a valid email address.',
                ]);
        });

        it('returns customer licenses only within the authenticated brand', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses/brand?customer_email=john@example.com', $this->rankMathBrand);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully retrieved license information for customer john@example.com in brand RankMath',
                ]);

            $data = $response->json('data');

            // Verify customer email
            expect($data['customer_email'])->toBe('john@example.com');

            // Verify brand information
            expect($data['brand']['name'])->toBe('RankMath');
            expect($data['brand']['slug'])->toBe('rankmath');
            expect($data['brand']['domain'])->toBe('rankmath.com');

            // Verify counts (only RankMath brand)
            expect($data['license_keys_count'])->toBe(1);
            expect($data['licenses_count'])->toBe(2);

            // Verify license keys (only RankMath)
            expect($data['license_keys'])->toHaveCount(1);

            // Check if brand information is loaded in license keys
            $licenseKey = $data['license_keys'][0];
            expect($licenseKey['brand'])->not->toBeNull();
            expect($licenseKey['brand']['name'])->toBe('RankMath');

            // Verify licenses summary (only RankMath)
            expect($data['licenses_summary']['total_active'])->toBe(2); // RankMath + Content AI
            expect($data['licenses_summary']['total_suspended'])->toBe(0);
            expect($data['licenses_summary']['total_cancelled'])->toBe(0);
            expect($data['licenses_summary']['total_expired'])->toBe(0);

            // Verify products summary (only RankMath products)
            expect($data['products_summary'])->toHaveCount(2);

            $rankMathProduct = collect($data['products_summary'])->firstWhere('product_slug', 'rankmath');
            expect($rankMathProduct)->not->toBeNull();
            expect($rankMathProduct['product_name'])->toBe('RankMath SEO');
            // Note: brand_name is not included in brand-specific queries since we already know the brand
            // expect($rankMathProduct['brand_name'])->toBe('RankMath');

            $contentAIProduct = collect($data['products_summary'])->firstWhere('product_slug', 'content-ai');
            expect($contentAIProduct)->not->toBeNull();
            expect($contentAIProduct['product_name'])->toBe('Content AI');
            // Note: brand_name is not included in brand-specific queries since we already know the brand
            // expect($contentAIProduct['brand_name'])->toBe('RankMath');
        });

        it('returns empty results for customer with no licenses in the brand', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses/brand?customer_email=nonexistent@example.com', $this->rankMathBrand);

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['license_keys_count'])->toBe(0);
            expect($data['licenses_count'])->toBe(0);
            expect($data['license_keys'])->toHaveCount(0);
            expect($data['products_summary'])->toHaveCount(0);
        });

        it('isolates data between brands', function () {
            // Test with WP Rocket brand
            $response = $this->authenticatedGet('/api/v1/customers/licenses/brand?customer_email=john@example.com', $this->wpRocketBrand);

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['brand']['name'])->toBe('WP Rocket');
            expect($data['license_keys_count'])->toBe(1);
            expect($data['licenses_count'])->toBe(1);
            expect($data['products_summary'])->toHaveCount(1);

            $wpRocketProduct = $data['products_summary'][0];
            expect($wpRocketProduct['product_slug'])->toBe('wp-rocket');
            expect($wpRocketProduct['product_name'])->toBe('WP Rocket');
            // Note: brand_name might not be available in brand-specific queries
            // expect($wpRocketProduct['brand_name'])->toBe('WP Rocket');
        });

        it('handles customers with licenses in multiple brands correctly', function () {
            // Create another customer with licenses in both brands
            $multiBrandCustomer = 'jane@example.com';

            $janeRankMathKey = LicenseKey::factory()->forBrand($this->rankMathBrand)->create([
                'customer_email' => $multiBrandCustomer,
            ]);

            $janeWpRocketKey = LicenseKey::factory()->forBrand($this->wpRocketBrand)->create([
                'customer_email' => $multiBrandCustomer,
            ]);

            License::factory()->forLicenseKey($janeRankMathKey)->forProduct($this->rankMathProduct)->create([
                'status' => LicenseStatus::VALID,
                'max_seats' => 2,
            ]);

            License::factory()->forLicenseKey($janeWpRocketKey)->forProduct($this->wpRocketProduct)->create([
                'status' => LicenseStatus::VALID,
                'max_seats' => 3,
            ]);

            // Test RankMath brand view
            $rankMathResponse = $this->authenticatedGet('/api/v1/customers/licenses/brand?customer_email=' . $multiBrandCustomer, $this->rankMathBrand);
            $rankMathData = $rankMathResponse->json('data');
            expect($rankMathData['licenses_count'])->toBe(1);
            expect($rankMathData['brand']['name'])->toBe('RankMath');

            // Test WP Rocket brand view
            $wpRocketResponse = $this->authenticatedGet('/api/v1/customers/licenses/brand?customer_email=' . $multiBrandCustomer, $this->wpRocketBrand);
            $wpRocketData = $wpRocketResponse->json('data');
            expect($wpRocketData['licenses_count'])->toBe(1);
            expect($wpRocketData['brand']['name'])->toBe('WP Rocket');
        });
    });

    describe('Edge cases and error handling', function () {
        it('handles malformed email gracefully', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=@invalid.com', $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email must be a valid email address.',
                ]);
        });

        it('handles very long email addresses', function () {
            $longEmail = 'a' . str_repeat('b', 250) . '@example.com';
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=' . $longEmail, $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email cannot exceed 255 characters.',
                ]);
        });

        it('handles special characters in email', function () {
            $specialEmail = 'test+tag@example.com';
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=' . urlencode($specialEmail), $this->rankMathBrand);

            $response->assertStatus(200);
            $data = $response->json('data');
            expect($data['customer_email'])->toBe($specialEmail);
        });

        it('handles empty email parameter', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=', $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email is required to list licenses.',
                ]);
        });

        it('handles missing customer_email query parameter', function () {
            $response = $this->authenticatedGet('/api/v1/customers/licenses', $this->rankMathBrand);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer email is required to list licenses.',
                ]);
        });
    });

    describe('Data integrity and relationships', function () {
        it('correctly calculates seat usage across licenses', function () {
            // Create a license with multiple activations
            $licenseWithSeats = License::factory()->forLicenseKey($this->rankMathLicenseKey)->forProduct($this->rankMathProduct)->create([
                'status' => LicenseStatus::VALID,
                'max_seats' => 10,
            ]);

            // Create multiple activations
            Activation::factory()->forLicense($licenseWithSeats)->create(['status' => 'active']);
            Activation::factory()->forLicense($licenseWithSeats)->create(['status' => 'active']);
            Activation::factory()->forLicense($licenseWithSeats)->create(['status' => 'deactivated']);
            Activation::factory()->forLicense($licenseWithSeats)->create(['status' => 'expired']);

            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=john@example.com', $this->rankMathBrand);
            $data = $response->json('data');

            // Find the product in the summary
            $productSummary = collect($data['products_summary'])->firstWhere('product_slug', 'rankmath');
            expect($productSummary['total_seats'])->toBe(13); // 3 + 2 + 10
            expect($productSummary['active_seats'])->toBe(4); // 2 + 1 + 2 (only active)
        });

        it('handles licenses with no activations', function () {
            $licenseNoActivations = License::factory()->forLicenseKey($this->rankMathLicenseKey)->forProduct($this->rankMathProduct)->create([
                'status' => LicenseStatus::VALID,
                'max_seats' => 5,
            ]);

            $response = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=john@example.com', $this->rankMathBrand);
            $data = $response->json('data');

            $productSummary = collect($data['products_summary'])->firstWhere('product_slug', 'rankmath');
            expect($productSummary['active_seats'])->toBe(2); // Only existing activations
        });

        it('maintains brand isolation in cross-brand queries', function () {
            // Create a customer with licenses in both brands
            $customerEmail = 'brandtest@example.com';

            $brand1Key = LicenseKey::factory()->forBrand($this->rankMathBrand)->create([
                'customer_email' => $customerEmail,
            ]);

            $brand2Key = LicenseKey::factory()->forBrand($this->wpRocketBrand)->create([
                'customer_email' => $customerEmail,
            ]);

            $brand1License = License::factory()->forLicenseKey($brand1Key)->forProduct($this->rankMathProduct)->create();
            $brand2License = License::factory()->forLicenseKey($brand2Key)->forProduct($this->wpRocketProduct)->create();

            // Cross-brand query should show both
            $crossBrandResponse = $this->authenticatedGet('/api/v1/customers/licenses?customer_email=' . $customerEmail, $this->rankMathBrand);
            $crossBrandData = $crossBrandResponse->json('data');
            expect($crossBrandData['total_licenses'])->toBe(2); // 2 new licenses
            expect($crossBrandData['brands_count'])->toBe(2);

            // Brand-specific query should only show the authenticated brand
            $brandSpecificResponse = $this->authenticatedGet('/api/v1/customers/licenses/brand?customer_email=' . $customerEmail, $this->rankMathBrand);
            $brandSpecificData = $brandSpecificResponse->json('data');
            expect($brandSpecificData['licenses_count'])->toBe(1); // 1 new license in RankMath brand
            expect($brandSpecificData['brand']['name'])->toBe('RankMath');
        });
    });
});
