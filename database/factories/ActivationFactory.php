<?php

namespace Database\Factories;

use App\Enums\ActivationStatus;
use App\Models\Activation;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activation>
 */
class ActivationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Activation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'instance_id' => 'instance_'.$this->faker->unique()->numberBetween(1, 1000),
            'instance_type' => 'wordpress',
            'instance_url' => 'https://site'.$this->faker->unique()->numberBetween(1, 1000).'.com',
            'machine_id' => 'machine_'.$this->faker->unique()->numberBetween(1, 1000),
            'status' => ActivationStatus::ACTIVE,
            'activated_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'deactivated_at' => null,
        ];
    }

    /**
     * Create an activation with a specific status.
     */
    public function withStatus(ActivationStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'deactivated_at' => $status === ActivationStatus::DEACTIVATED ? now() : null,
        ]);
    }

    /**
     * Create an active activation.
     */
    public function active(): static
    {
        return $this->withStatus(ActivationStatus::ACTIVE);
    }

    /**
     * Create a deactivated activation.
     */
    public function deactivated(): static
    {
        return $this->withStatus(ActivationStatus::DEACTIVATED);
    }

    /**
     * Create an expired activation.
     */
    public function expired(): static
    {
        return $this->withStatus(ActivationStatus::EXPIRED);
    }

    /**
     * Create an activation for a specific license.
     */
    public function forLicense(License $license): static
    {
        return $this->state(fn (array $attributes) => [
            'license_id' => $license->id,
        ]);
    }

    /**
     * Create an activation for a specific instance.
     */
    public function forInstance(string $instanceId, ?string $instanceUrl = null): static
    {
        return $this->state(fn (array $attributes) => [
            'instance_id' => $instanceId,
            'instance_url' => $instanceUrl ?? $this->faker->url(),
        ]);
    }

    /**
     * Create an activation for a WordPress site.
     */
    public function forWordPressSite(string $siteUrl): static
    {
        return $this->state(fn (array $attributes) => [
            'instance_type' => 'wordpress',
            'instance_url' => $siteUrl,
            'instance_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Create an activation for a machine.
     */
    public function forMachine(string $machineId): static
    {
        return $this->state(fn (array $attributes) => [
            'instance_type' => 'machine',
            'machine_id' => $machineId,
            'instance_id' => $machineId,
        ]);
    }
}
