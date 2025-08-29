<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Product '.$this->faker->unique()->numberBetween(1, 1000);

        return [
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Description for '.$name,
            'max_seats' => $this->faker->optional()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a product with seat management.
     */
    public function withSeats(int $maxSeats = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'max_seats' => $maxSeats,
        ]);
    }

    /**
     * Create a product without seat management.
     */
    public function withoutSeats(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_seats' => null,
        ]);
    }

    /**
     * Create a product for a specific brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Create a product with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ]);
    }
}
