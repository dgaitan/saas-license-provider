<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = Brand::all();

        foreach ($brands as $brand) {
            $products = $this->getProductsForBrand($brand->slug);

            foreach ($products as $productData) {
                Product::create([
                    'brand_id' => $brand->id,
                    ...$productData,
                ]);
            }
        }
    }

    /**
     * Get products for a specific brand.
     */
    private function getProductsForBrand(string $brandSlug): array
    {
        return match ($brandSlug) {
            'rankmath' => [
                [
                    'name' => 'RankMath SEO',
                    'slug' => 'rankmath-seo',
                    'description' => 'Complete SEO solution for WordPress',
                    'max_seats' => 5,
                    'is_active' => true,
                ],
                [
                    'name' => 'Content AI',
                    'slug' => 'content-ai',
                    'description' => 'AI-powered content optimization',
                    'max_seats' => 3,
                    'is_active' => true,
                ],
            ],
            'wp-rocket' => [
                [
                    'name' => 'WP Rocket',
                    'slug' => 'wp-rocket',
                    'description' => 'Premium WordPress caching plugin',
                    'max_seats' => 10,
                    'is_active' => true,
                ],
                [
                    'name' => 'RocketCDN',
                    'slug' => 'rocketcdn',
                    'description' => 'Global CDN for WordPress',
                    'max_seats' => null, // Unlimited
                    'is_active' => true,
                ],
            ],
            'imagify' => [
                [
                    'name' => 'Imagify',
                    'slug' => 'imagify',
                    'description' => 'Image optimization and compression',
                    'max_seats' => 20,
                    'is_active' => true,
                ],
            ],
            'backwpup' => [
                [
                    'name' => 'BackWPup',
                    'slug' => 'backwpup',
                    'description' => 'WordPress backup solution',
                    'max_seats' => 15,
                    'is_active' => true,
                ],
            ],
            default => [],
        };
    }
}
