<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\LicenseKey;
use App\Models\License;
use App\Models\Activation;
use App\Enums\LicenseStatus;
use App\Enums\ActivationStatus;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->product = Product::factory()->forBrand($this->brand)->create();
    $this->licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();
    $this->license = License::factory()
        ->forLicenseKey($this->licenseKey)
        ->forProduct($this->product)
        ->create();
});

describe('License Model', function () {
    it('can be created with factory', function () {
        expect($this->license)->toBeInstanceOf(License::class);
        expect($this->license->uuid)->toBeString();
        expect($this->license->license_key_id)->toBe($this->licenseKey->id);
        expect($this->license->product_id)->toBe($this->product->id);
        expect($this->license->status)->toBe(LicenseStatus::VALID);
        expect($this->license->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('belongs to a license key', function () {
        expect($this->license->licenseKey)->toBeInstanceOf(LicenseKey::class);
        expect($this->license->licenseKey->id)->toBe($this->licenseKey->id);
    });

    it('belongs to a product', function () {
        expect($this->license->product)->toBeInstanceOf(Product::class);
        expect($this->license->product->id)->toBe($this->product->id);
    });

    it('has activations relationship', function () {
        $activation = Activation::factory()->forLicense($this->license)->create();

        expect($this->license->activations)->toHaveCount(1);
        expect($this->license->activations->first())->toBeInstanceOf(Activation::class);
        expect($this->license->activations->first()->id)->toBe($activation->id);
    });

    it('can be created with different statuses', function () {
        $suspendedLicense = License::factory()->suspended()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();
        $cancelledLicense = License::factory()->cancelled()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();
        $expiredLicense = License::factory()->expired()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();

        expect($suspendedLicense->status)->toBe(LicenseStatus::SUSPENDED);
        expect($cancelledLicense->status)->toBe(LicenseStatus::CANCELLED);
        expect($expiredLicense->status)->toBe(LicenseStatus::EXPIRED);
    });

    it('can be created with seat management', function () {
        $licenseWithSeats = License::factory()->withSeats(5)->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();

        expect($licenseWithSeats->max_seats)->toBe(5);
        expect($licenseWithSeats->supportsSeats())->toBeTrue();
    });

    it('can be created without seat management', function () {
        $licenseWithoutSeats = License::factory()->withoutSeats()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();

        expect($licenseWithoutSeats->max_seats)->toBeNull();
        expect($licenseWithoutSeats->supportsSeats())->toBeFalse();
    });

    it('can be created that never expires', function () {
        $neverExpiresLicense = License::factory()->neverExpires()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();

        expect($neverExpiresLicense->expires_at)->toBeNull();
    });

    it('validates license correctly', function () {
        expect($this->license->isValid())->toBeTrue();

        $suspendedLicense = License::factory()->suspended()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();
        expect($suspendedLicense->isValid())->toBeFalse();

        $expiredLicense = License::factory()->expired()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();
        expect($expiredLicense->isValid())->toBeFalse();
    });

    it('validates license with null expiration', function () {
        $neverExpiresLicense = License::factory()->neverExpires()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();

        expect($neverExpiresLicense->isValid())->toBeTrue();
    });

    it('calculates seat usage correctly', function () {
        $license = License::factory()->withSeats(5)->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();

        // Create some activations
        Activation::factory()->active()->forLicense($license)->createMany([
            ['instance_id' => 'site1'],
            ['instance_id' => 'site2'],
        ]);

        Activation::factory()->deactivated()->forLicense($license)->create(['instance_id' => 'site3']);

        expect($license->getUsedSeats())->toBe(2);
        expect($license->getRemainingSeats())->toBe(3);
        expect($license->hasAvailableSeats())->toBeTrue();
    });

    it('handles licenses without seat management', function () {
        $license = License::factory()->withoutSeats()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();

        expect($license->getUsedSeats())->toBe(0);
        expect($license->getRemainingSeats())->toBe(0);
        expect($license->hasAvailableSeats())->toBeTrue(); // Always true for licenses without seat management
    });

    it('can get active activations', function () {
        $activeActivation = Activation::factory()->active()->forLicense($this->license)->create();
        $deactivatedActivation = Activation::factory()->deactivated()->forLicense($this->license)->create();

        $activeActivations = $this->license->activeActivations()->get();

        expect($activeActivations)->toHaveCount(1);
        expect($activeActivations->first()->id)->toBe($activeActivation->id);
    });

    it('can renew license', function () {
        $originalExpiry = $this->license->expires_at;

        $this->license->renew(365);

        expect($this->license->status)->toBe(LicenseStatus::VALID);
        expect($this->license->expires_at->timestamp)->toBeGreaterThan($originalExpiry->timestamp);
    });

    it('can suspend license', function () {
        $this->license->suspend();

        expect($this->license->status)->toBe(LicenseStatus::SUSPENDED);
    });

    it('can resume license', function () {
        $this->license->suspend();
        $this->license->resume();

        expect($this->license->status)->toBe(LicenseStatus::VALID);
    });

    it('can cancel license', function () {
        $this->license->cancel();

        expect($this->license->status)->toBe(LicenseStatus::CANCELLED);
    });

    it('checks if license is expired', function () {
        expect($this->license->isExpired())->toBeFalse();

        $expiredLicense = License::factory()->expired()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();
        expect($expiredLicense->isExpired())->toBeTrue();
    });
});
