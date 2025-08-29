<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\License>
 */
class LicenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = License::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_key_id' => LicenseKey::factory(),
            'product_id' => Product::factory(),
            'status' => LicenseStatus::VALID,
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'max_seats' => $this->faker->optional()->numberBetween(1, 10),
        ];
    }

    /**
     * Create a license with a specific status.
     */
    public function withStatus(LicenseStatus $status): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Create a valid license.
     */
    public function valid(): static
    {
        return $this->withStatus(LicenseStatus::VALID);
    }

    /**
     * Create a suspended license.
     */
    public function suspended(): static
    {
        return $this->withStatus(LicenseStatus::SUSPENDED);
    }

    /**
     * Create a cancelled license.
     */
    public function cancelled(): static
    {
        return $this->withStatus(LicenseStatus::CANCELLED);
    }

    /**
     * Create an expired license.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LicenseStatus::EXPIRED,
            'expires_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Create a license with seat management.
     */
    public function withSeats(int $maxSeats = 5): static
    {
        return $this->state(fn(array $attributes) => [
            'max_seats' => $maxSeats,
        ]);
    }

    /**
     * Create a license without seat management.
     */
    public function withoutSeats(): static
    {
        return $this->state(fn(array $attributes) => [
            'max_seats' => null,
        ]);
    }

    /**
     * Create a license that never expires.
     */
    public function neverExpires(): static
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Create a license for a specific license key.
     */
    public function forLicenseKey(LicenseKey $licenseKey): static
    {
        return $this->state(fn(array $attributes) => [
            'license_key_id' => $licenseKey->id,
        ]);
    }

    /**
     * Create a license for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn(array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
