<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'RankMath',
                'slug' => 'rankmath',
                'domain' => 'rankmath.com',
                'api_key' => 'brand_rankmath_test_key_123456789',
                'is_active' => true,
            ],
            [
                'name' => 'WP Rocket',
                'slug' => 'wp-rocket',
                'domain' => 'wp-rocket.me',
                'api_key' => 'brand_wprocket_test_key_123456789',
                'is_active' => true,
            ],
            [
                'name' => 'Imagify',
                'slug' => 'imagify',
                'domain' => 'imagify.io',
                'api_key' => 'brand_imagify_test_key_123456789',
                'is_active' => true,
            ],
            [
                'name' => 'BackWPup',
                'slug' => 'backwpup',
                'domain' => 'backwpup.com',
                'api_key' => 'brand_backwpup_test_key_123456789',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brandData) {
            Brand::create($brandData);
        }
    }
}
