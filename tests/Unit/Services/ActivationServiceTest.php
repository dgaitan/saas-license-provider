<?php

use App\Enums\ActivationStatus;
use App\Enums\LicenseStatus;
use App\Models\Activation;
use App\Models\Brand;
use App\Models\License;
use App\Models\Product;
use App\Repositories\Interfaces\ActivationRepositoryInterface;
use App\Services\Api\V1\Product\ActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('ActivationService - US5: End-user product or customer can deactivate a seat', function () {
    beforeEach(function () {
        $this->activationRepository = Mockery::mock(ActivationRepositoryInterface::class);
        $this->activationService = new ActivationService($this->activationRepository);

        // Create test data
        $this->brand = Brand::factory()->create();
        $this->product = Product::factory()->forBrand($this->brand)->create(['max_seats' => 5]);
        $this->license = License::factory()
            ->forBrand($this->brand)
            ->forProduct($this->product)
            ->create([
                'status' => LicenseStatus::VALID,
                'max_seats' => 5,
                'expires_at' => now()->addYear(),
            ]);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('getSeatUsage', function () {
        it('returns seat usage information for license with seat management', function () {
            // Create some activations
            Activation::factory()->count(3)->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
            ]);

            $seatUsage = $this->activationService->getSeatUsage($this->license);

            expect($seatUsage)->toHaveKey('supports_seat_management');
            expect($seatUsage['supports_seat_management'])->toBeTrue();
            expect($seatUsage['total_seats'])->toBe(5);
            expect($seatUsage['used_seats'])->toBe(3);
            expect($seatUsage['available_seats'])->toBe(2);
            expect($seatUsage['usage_percentage'])->toBe(60.0);
            expect($seatUsage['active_activations'])->toHaveCount(3);
        });

        it('returns appropriate message for license without seat management', function () {
            $licenseWithoutSeats = License::factory()
                ->forBrand($this->brand)
                ->forProduct($this->product)
                ->create([
                    'status' => LicenseStatus::VALID,
                    'max_seats' => null,
                ]);

            $seatUsage = $this->activationService->getSeatUsage($licenseWithoutSeats);

            expect($seatUsage)->toHaveKey('supports_seat_management');
            expect($seatUsage['supports_seat_management'])->toBeFalse();
            expect($seatUsage['message'])->toBe('This license does not support seat management');
        });

        it('handles license with no active activations', function () {
            $seatUsage = $this->activationService->getSeatUsage($this->license);

            expect($seatUsage['used_seats'])->toBe(0);
            expect($seatUsage['available_seats'])->toBe(5);
            expect($seatUsage['usage_percentage'])->toBe(0.0);
            expect($seatUsage['active_activations'])->toHaveCount(0);
        });

        it('includes detailed activation information', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'test-instance-123',
                'instance_type' => 'wordpress',
                'instance_url' => 'https://example.com',
                'machine_id' => 'machine-456',
            ]);

            $seatUsage = $this->activationService->getSeatUsage($this->license);

            expect($seatUsage['active_activations'])->toHaveCount(1);
            expect($seatUsage['active_activations'][0])->toHaveKey('instance_id');
            expect($seatUsage['active_activations'][0])->toHaveKey('instance_type');
            expect($seatUsage['active_activations'][0])->toHaveKey('instance_url');
            expect($seatUsage['active_activations'][0])->toHaveKey('machine_id');
            expect($seatUsage['active_activations'][0])->toHaveKey('activated_at');
            expect($seatUsage['active_activations'][0])->toHaveKey('last_activity');
        });
    });

    describe('forceDeactivateAllSeats', function () {
        it('deactivates all active seats for a license', function () {
            // Create multiple active activations
            $activations = Activation::factory()->count(3)->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
            ]);

            $deactivatedCount = $this->activationService->forceDeactivateAllSeats($this->license, 'Administrative cleanup');

            expect($deactivatedCount)->toBe(3);

            // Check that all activations are now deactivated
            foreach ($activations as $activation) {
                $activation->refresh();
                expect($activation->status)->toBe(ActivationStatus::DEACTIVATED);
                expect($activation->deactivation_reason)->toBe('Administrative cleanup');
            }
        });

        it('returns 0 when no active seats exist', function () {
            $deactivatedCount = $this->activationService->forceDeactivateAllSeats($this->license, 'No reason');

            expect($deactivatedCount)->toBe(0);
        });

        it('handles mixed activation statuses correctly', function () {
            // Create mixed status activations
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
            ]);
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::DEACTIVATED,
            ]);
            Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
            ]);

            $deactivatedCount = $this->activationService->forceDeactivateAllSeats($this->license, 'Mixed cleanup');

            expect($deactivatedCount)->toBe(2); // Only active ones should be deactivated
        });

        it('logs each deactivation with proper information', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'test-instance',
            ]);

            // Mock the Log facade to capture log calls
            Log::shouldReceive('info')->once()->with('Seat force deactivated', Mockery::on(function ($args) use ($activation) {
                return $args['license_id'] === $this->license->id &&
                    $args['activation_id'] === $activation->id &&
                    $args['instance_id'] === 'test-instance' &&
                    $args['reason'] === 'Test reason' &&
                    isset($args['deactivated_at']);
            }));

            $this->activationService->forceDeactivateAllSeats($this->license, 'Test reason');
        });
    });

    describe('deactivateLicense - Enhanced US5 functionality', function () {
        it('deactivates license and logs seat deactivation', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::ACTIVE,
                'instance_id' => 'test-instance',
                'instance_type' => 'wordpress',
            ]);

            // Mock the repository to return the activation
            $this->activationRepository->shouldReceive('findByLicenseAndInstance')
                ->once()
                ->andReturn($activation);

            // Mock the Log facade to capture log calls
            Log::shouldReceive('info')->once()->with('Seat deactivated', Mockery::on(function ($args) {
                return $args['license_id'] === $this->license->id &&
                    $args['instance_id'] === 'test-instance' &&
                    $args['instance_type'] === 'wordpress' &&
                    isset($args['deactivated_at']) &&
                    isset($args['available_seats_before']) &&
                    isset($args['available_seats_after']);
            }));

            $result = $this->activationService->deactivateLicense(
                $this->license,
                'test-instance',
                'wordpress'
            );

            expect($result)->toBeInstanceOf(Activation::class);
            expect($result->status)->toBe(ActivationStatus::DEACTIVATED);
        });

        it('throws exception when no activation found', function () {
            $this->activationRepository->shouldReceive('findByLicenseAndInstance')
                ->once()
                ->andReturn(null);

            expect(function () {
                $this->activationService->deactivateLicense(
                    $this->license,
                    'non-existent',
                    'wordpress'
                );
            })->toThrow(\Exception::class, 'No activation found for this instance');
        });

        it('throws exception when activation is already deactivated', function () {
            $activation = Activation::factory()->forLicense($this->license)->create([
                'status' => ActivationStatus::DEACTIVATED,
            ]);

            $this->activationRepository->shouldReceive('findByLicenseAndInstance')
                ->once()
                ->andReturn($activation);

            expect(function () {
                $this->activationService->deactivateLicense(
                    $this->license,
                    'test-instance',
                    'wordpress'
                );
            })->toThrow(\Exception::class, 'License is not currently activated for this instance');
        });
    });
});
