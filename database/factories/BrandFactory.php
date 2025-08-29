<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Brand ' . $this->faker->unique()->numberBetween(1, 1000);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'domain' => 'example' . $this->faker->unique()->numberBetween(1, 1000) . '.com',
            'api_key' => Brand::generateApiKey(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the brand is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a brand with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
