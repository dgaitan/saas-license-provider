<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\LicenseKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenseKey>
 */
class LicenseKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LicenseKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'key' => LicenseKey::generateKey(),
            'customer_email' => $this->faker->unique()->safeEmail(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the license key is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a license key for a specific brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Create a license key for a specific customer.
     */
    public function forCustomer(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_email' => $email,
        ]);
    }
}
