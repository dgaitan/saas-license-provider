<?php

namespace App\Console\Commands;

use App\Enums\ActivationStatus;
use App\Enums\LicenseStatus;
use App\Models\Activation;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Artisan command to create initial test data for API testing.
 *
 * This command creates a complete test environment with:
 * - Brand with authentication token
 * - Multiple products
 * - License keys and licenses
 * - Sample activations
 *
 * Perfect for reviewers to test API endpoints.
 */
class CreateTestDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-test-data {--brand-name= : Name of the brand to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create initial test data for API testing (brands, products, licenses, activations)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Creating Test Data for License Service API Testing');
        $this->newLine();

        // Get brand name from user input or option
        $brandName = $this->getBrandName();
        if (!$brandName) {
            return Command::FAILURE;
        }

        try {
            // Create brand
            $brand = $this->createBrand($brandName);
            $this->info(sprintf("✅ Brand '%s' created successfully!", $brand->name));

            // Create products
            $products = $this->createProducts($brand);
            $this->info(sprintf("✅ %d products created successfully!", $products->count()));

            // Create license keys
            $licenseKeys = $this->createLicenseKeys($brand);
            $this->info(sprintf("✅ %d license keys created successfully!", $licenseKeys->count()));

            // Create licenses
            $licenses = $this->createLicenses($licenseKeys, $products);
            $this->info(sprintf("✅ %d licenses created successfully!", $licenses->count()));

            // Create activations
            $activations = $this->createActivations($licenses);
            $this->info(sprintf("✅ %d activations created successfully!", $activations->count()));

            // Display results
            $this->displayResults($brand, $products, $licenseKeys, $licenses, $activations);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error(sprintf("❌ Error creating test data: %s", $e->getMessage()));
            $this->error(sprintf("Stack trace: %s", $e->getTraceAsString()));
            return Command::FAILURE;
        }
    }

    /**
     * Get brand name from user input or command option.
     */
    private function getBrandName(): ?string
    {
        $brandName = $this->option('brand-name');

        if (!$brandName) {
            $brandName = $this->ask('Enter the brand name (e.g., "RankMath", "WP Rocket"):');
        }

        if (empty(trim($brandName))) {
            $this->error('❌ Brand name cannot be empty!');
            return null;
        }

        return trim($brandName);
    }

    /**
     * Create a brand with the given name
     */
    private function createBrand(string $name): Brand
    {
        $this->info("Creating brand: {$name}...");

        $brand = Brand::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'domain' => Str::slug($name) . '.com',
            'api_key' => Brand::generateApiKey(),
            'is_active' => true,
        ]);

        return $brand;
    }

    /**
     * Create sample products for the brand.
     */
    private function createProducts(Brand $brand): \Illuminate\Support\Collection
    {
        $this->info(sprintf("Creating products for brand: %s...", $brand->name));

        $productData = [
            [
                'name' => 'RankMath SEO',
                'slug' => 'rankmath-seo',
                'description' => 'Complete SEO solution for WordPress',
                'max_seats' => 5,
            ],
            [
                'name' => 'Content AI',
                'slug' => 'content-ai',
                'description' => 'AI-powered content generation and optimization',
                'max_seats' => 3,
            ],
            [
                'name' => 'Analytics Pro',
                'slug' => 'analytics-pro',
                'description' => 'Advanced analytics and reporting dashboard',
                'max_seats' => 10,
            ],
        ];

        $products = collect();
        foreach ($productData as $data) {
            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'max_seats' => $data['max_seats'],
                'is_active' => true,
            ]);

            $products->push($product);
            $this->info(sprintf("   ✅ %s (UUID: %s, Max Seats: %d)", $product->name, $product->uuid, $product->max_seats));
        }

        return $products;
    }

    /**
     * Create sample license keys for the brand.
     */
    private function createLicenseKeys(Brand $brand): \Illuminate\Support\Collection
    {
        $this->info(sprintf("Creating license keys for brand: %s...", $brand->name));

        $customerEmails = [
            'john.doe@example.com',
            'jane.smith@example.com',
            'bob.wilson@example.com',
        ];

        $licenseKeys = collect();
        foreach ($customerEmails as $email) {
            $licenseKey = LicenseKey::create([
                'brand_id' => $brand->id,
                'key' => LicenseKey::generateKey(),
                'customer_email' => $email,
                'is_active' => true,
            ]);

            $licenseKeys->push($licenseKey);
            $this->info(sprintf("   ✅ License Key for %s (UUID: %s)", $email, $licenseKey->uuid));
        }

        return $licenseKeys;
    }

    /**
     * Create sample licenses for the license keys and products.
     */
    private function createLicenses(\Illuminate\Support\Collection $licenseKeys, \Illuminate\Support\Collection $products): \Illuminate\Support\Collection
    {
        $this->info("Creating licenses...");

        $licenses = collect();
        $statuses = [LicenseStatus::VALID, LicenseStatus::VALID, LicenseStatus::SUSPENDED];

        foreach ($licenseKeys as $index => $licenseKey) {
            $product = $products->get($index % $products->count());
            $status = $statuses[$index % count($statuses)];

            $license = License::create([
                'license_key_id' => $licenseKey->id,
                'product_id' => $product->id,
                'status' => $status,
                'expires_at' => now()->addYear(),
                'max_seats' => $product->max_seats,
            ]);

            $licenses->push($license);
            $this->info(sprintf("   ✅ License for %s (UUID: %s, Status: %s, Max Seats: %d)", $product->name, $license->uuid, $license->status->value, $license->max_seats));
        }

        return $licenses;
    }

    /**
     * Create sample activations for the licenses.
     */
    private function createActivations(\Illuminate\Support\Collection $licenses): \Illuminate\Support\Collection
    {
        $this->info("Creating activations...");

        $activations = collect();
        $instanceTypes = ['wordpress', 'wordpress', 'machine'];
        $statuses = [ActivationStatus::ACTIVE, ActivationStatus::ACTIVE, ActivationStatus::DEACTIVATED];

        foreach ($licenses as $index => $license) {
            $instanceType = $instanceTypes[$index % count($instanceTypes)];
            $status = $statuses[$index % count($statuses)];

            $activation = Activation::create([
                'license_id' => $license->id,
                'instance_id' => sprintf('instance-%d', $index + 1),
                'instance_type' => $instanceType,
                'instance_url' => sprintf('https://example%d.com', $index + 1),
                'machine_id' => $instanceType === 'machine' ? sprintf('machine-%d', $index + 1) : null,
                'status' => $status,
                'activated_at' => now()->subDays(rand(1, 30)),
                'deactivated_at' => $status === ActivationStatus::DEACTIVATED ? now()->subDays(rand(1, 7)) : null,
                'deactivation_reason' => $status === ActivationStatus::DEACTIVATED ? 'Testing deactivation' : null,
            ]);

            $activations->push($activation);
            $this->info(sprintf("   ✅ Activation for %s (UUID: %s, Status: %s)", $activation->instance_id, $activation->uuid, $activation->status->value));
        }

        return $activations;
    }

    /**
     * Display the results and provide usage instructions
     */
    private function displayResults(Brand $brand, Collection $products, Collection $licenseKeys, Collection $licenses, Collection $activations): void
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info('🎉 Test Data Created Successfully!');
        $this->info(str_repeat('=', 60));

        $this->info("\n📊 Created Entities:");
        $this->info("  • Brand: {$brand->name} (UUID: {$brand->uuid})");
        $this->info("  • Products: {$products->count()}");
        $this->info("  • License Keys: {$licenseKeys->count()}");
        $this->info("  • Licenses: {$licenses->count()}");
        $this->info("  • Activations: {$activations->count()}");

        $this->info("\n🔑 **Brand Authentication Instructions:**");
        $this->info('  • Use the Brand API Key for authentication');
        $this->info("  • Format: X-Tenant: {$brand->api_key}");
        $this->info('  • Add this header to all brand-facing API requests');
        $this->info('  • Example: curl -H "X-Tenant: ' . $brand->api_key . '" http://localhost:8002/api/v1/products');
        $this->info('');
        $this->info('📋 **Available Endpoints:**');
        $this->info('  • Brand-facing (requires X-Tenant header):');
        $this->info('    - POST /api/v1/license-keys');
        $this->info('    - POST /api/v1/licenses');
        $this->info('    - PATCH /api/v1/licenses/{uuid}/renew');
        $this->info('    - PATCH /api/v1/license-keys/{uuid}');
        $this->info('    - POST /api/v1/licenses/{uuid}/force-deactivate-seats');
        $this->info('    - GET /api/v1/products');
        $this->info('    - GET /api/v1/licenses');
        $this->info('    - GET /api/v1/license-keys');
        $this->info('  • Product-facing (no authentication required):');
        $this->info('    - POST /api/v1/licenses/{uuid}/activate');
        $this->info('    - POST /api/v1/licenses/{uuid}/deactivate');
        $this->info('    - POST /api/v1/license-keys/{uuid}/status');

        $this->info("\n🚀 Ready to test your API endpoints!");
        $this->info("  • Start the server: php artisan serve --port=8002");
        $this->info("  • Test with cURL or your preferred API client");
        $this->info("  • View API docs at: http://localhost:8002/docs/api");
    }
}
