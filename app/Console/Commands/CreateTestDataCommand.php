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
     * Create a new brand with authentication token.
     */
    private function createBrand(string $name): Brand
    {
        $this->info(sprintf("Creating brand: %s...", $name));

        $brand = Brand::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'domain' => sprintf('%s.com', Str::slug($name)),
            'api_key' => Brand::generateApiKey(),
            'is_active' => true,
        ]);

        // Create Sanctum token for API authentication
        $token = $brand->createBrandToken('test-token');

        $this->info(sprintf("   Brand ID: %d", $brand->id));
        $this->info(sprintf("   Brand UUID: %s", $brand->uuid));
        $this->info(sprintf("   API Key: %s", $brand->api_key));
        $this->info(sprintf("   Auth Token: %s", $token));

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
     * Display comprehensive results and usage instructions.
     */
    private function displayResults(Brand $brand, \Illuminate\Support\Collection $products, \Illuminate\Support\Collection $licenseKeys, \Illuminate\Support\Collection $licenses, \Illuminate\Support\Collection $activations): void
    {
        $this->newLine();
        $this->info('🎉 Test Data Creation Complete!');
        $this->newLine();

        // Summary
        $this->table(
            ['Entity', 'Count'],
            [
                ['Brands', 1],
                ['Products', $products->count()],
                ['License Keys', $licenseKeys->count()],
                ['Licenses', $licenses->count()],
                ['Activations', $activations->count()],
            ]
        );

        $this->newLine();
        $this->info('🔑 Authentication Information');
        $this->line(sprintf("Brand Name: %s", $brand->name));
        $this->line(sprintf("Brand UUID: %s", $brand->uuid));
        $this->line(sprintf("API Key: %s", $brand->api_key));
        $this->line(sprintf("Auth Token: %s", $brand->tokens->first()->token));

        $this->newLine();
        $this->info('📋 Sample Data Created');

        // Products
        $this->line("Products:");
        foreach ($products as $product) {
            $this->line(sprintf("  • %s (UUID: %s, Max Seats: %d)", $product->name, $product->uuid, $product->max_seats));
        }

        // License Keys
        $this->line("License Keys:");
        foreach ($licenseKeys as $licenseKey) {
            $this->line(sprintf("  • %s (UUID: %s)", $licenseKey->customer_email, $licenseKey->uuid));
        }

        // Licenses
        $this->line("Licenses:");
        foreach ($licenses as $license) {
            $product = $products->firstWhere('id', $license->product_id);
            $this->line(sprintf("  • %s (UUID: %s, Status: %s)", $product->name, $license->uuid, $license->status->value));
        }

        $this->newLine();
        $this->info('🚀 How to Use the Brand Auth Token');

        $this->line('1. Use the Authorization header in your API requests:');
        $this->line(sprintf("   Authorization: Bearer %s", $brand->tokens->first()->token));

        $this->line('2. Test the API endpoints:');
        $this->line('   Base URL: http://localhost:8002/api/v1');

        $this->line('3. Example cURL commands:');
        $this->line('   # List license keys');
        $this->line('   curl -X GET "http://localhost:8002/api/v1/license-keys" \\');
        $this->line(sprintf("     -H \"Authorization: Bearer %s\"", $brand->tokens->first()->token));

        $this->line('   # Create a new license key');
        $this->line('   curl -X POST "http://localhost:8002/api/v1/license-keys" \\');
        $this->line(sprintf("     -H \"Authorization: Bearer %s\" \\", $brand->tokens->first()->token));
        $this->line('     -H "Content-Type: application/json" \\');
        $this->line('     -d \'{"customer_email": "newuser@example.com"}\'');

        $this->line('   # List customer licenses across all brands (US6)');
        $this->line('   curl -X GET "http://localhost:8002/api/v1/customers/licenses?customer_email=john.doe@example.com" \\');
        $this->line(sprintf("     -H \"Authorization: Bearer %s\"", $brand->tokens->first()->token));

        $this->newLine();
        $this->info('📚 Available API Endpoints');
        $this->line('• POST /api/v1/license-keys - Create license key');
        $this->line('• GET /api/v1/license-keys/{uuid} - Get license key');
        $this->line('• POST /api/v1/licenses - Create license');
        $this->line('• GET /api/v1/licenses/{uuid} - Get license');
        $this->line('• PATCH /api/v1/licenses/{uuid}/renew - Renew license');
        $this->line('• PATCH /api/v1/licenses/{uuid}/suspend - Suspend license');
        $this->line('• PATCH /api/v1/licenses/{uuid}/resume - Resume license');
        $this->line('• PATCH /api/v1/licenses/{uuid}/cancel - Cancel license');
        $this->line('• GET /api/v1/customers/licenses - List customer licenses across brands (US6)');
        $this->line('• GET /api/v1/customers/licenses/brand - List customer licenses within brand (US6)');

        $this->newLine();
        $this->info('🧪 Testing Tips');
        $this->line('• Start the server: php artisan serve --host=0.0.0.0 --port=8002');
        $this->line('• Use Postman or similar tool for easier API testing');
        $this->line('• Check the response status codes and error messages');
        $this->line('• Test both authenticated and unauthenticated endpoints');
        $this->line('• Verify multi-tenancy by creating multiple brands');

        $this->newLine();
        $this->warn('⚠️  Note: This is test data. Do not use in production!');
        $this->newLine();
    }
}
