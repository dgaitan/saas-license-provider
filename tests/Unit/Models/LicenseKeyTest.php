<?php

use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();
});

describe('LicenseKey Model', function () {
    it('can be created with factory', function () {
        expect($this->licenseKey)->toBeInstanceOf(LicenseKey::class);
        expect($this->licenseKey->uuid)->toBeString();
        expect($this->licenseKey->key)->toBeString();
        expect($this->licenseKey->customer_email)->toBeString();
        expect($this->licenseKey->brand_id)->toBe($this->brand->id);
        expect($this->licenseKey->is_active)->toBeTrue();
    });

    it('belongs to a brand', function () {
        expect($this->licenseKey->brand)->toBeInstanceOf(Brand::class);
        expect($this->licenseKey->brand->id)->toBe($this->brand->id);
    });

    it('has licenses relationship', function () {
        $license = License::factory()->forLicenseKey($this->licenseKey)->create();

        expect($this->licenseKey->licenses)->toHaveCount(1);
        expect($this->licenseKey->licenses->first())->toBeInstanceOf(License::class);
        expect($this->licenseKey->licenses->first()->id)->toBe($license->id);
    });

    it('generates unique license keys', function () {
        $licenseKey1 = LicenseKey::factory()->create();
        $licenseKey2 = LicenseKey::factory()->create();

        expect($licenseKey1->key)->not->toBe($licenseKey2->key);
        expect(strlen($licenseKey1->key))->toBe(32);
    });

    it('can be created for specific customer', function () {
        $email = 'test@example.com';
        $licenseKey = LicenseKey::factory()->forCustomer($email)->forBrand($this->brand)->create();

        expect($licenseKey->customer_email)->toBe($email);
    });

    it('can be marked as inactive', function () {
        $inactiveLicenseKey = LicenseKey::factory()->inactive()->forBrand($this->brand)->create();

        expect($inactiveLicenseKey->is_active)->toBeFalse();
        expect($inactiveLicenseKey->isActive())->toBeFalse();
    });

    it('can scope to active license keys', function () {
        LicenseKey::factory()->inactive()->forBrand($this->brand)->create();
        $activeLicenseKey = LicenseKey::factory()->forBrand($this->brand)->create();

        $activeLicenseKeys = LicenseKey::active()->get();

        expect($activeLicenseKeys)->toHaveCount(2); // Including the one from beforeEach
        expect($activeLicenseKeys->pluck('is_active')->unique())->toContain(true);
    });

    it('can get active licenses', function () {
        $validLicense = License::factory()->valid()->forLicenseKey($this->licenseKey)->create();
        $suspendedLicense = License::factory()->suspended()->forLicenseKey($this->licenseKey)->create();

        $activeLicenses = $this->licenseKey->activeLicenses()->get();

        expect($activeLicenses)->toHaveCount(1);
        expect($activeLicenses->first()->id)->toBe($validLicense->id);
    });

    it('calculates seat usage correctly', function () {
        $license1 = License::factory()->withSeats(3)->forLicenseKey($this->licenseKey)->create();
        $license2 = License::factory()->withSeats(2)->forLicenseKey($this->licenseKey)->create();

        // Create some activations
        $license1->activations()->createMany([
            ['instance_id' => 'site1', 'status' => 'active', 'activated_at' => now()],
            ['instance_id' => 'site2', 'status' => 'active', 'activated_at' => now()],
        ]);

        $license2->activations()->createMany([
            ['instance_id' => 'site3', 'status' => 'active', 'activated_at' => now()],
        ]);

        expect($this->licenseKey->getTotalSeats())->toBe(5);
        expect($this->licenseKey->getUsedSeats())->toBe(3);
        expect($this->licenseKey->getRemainingSeats())->toBe(2);
    });

    it('handles licenses without seat management', function () {
        $license = License::factory()->withoutSeats()->forLicenseKey($this->licenseKey)->create();

        expect($this->licenseKey->getTotalSeats())->toBe(0);
        expect($this->licenseKey->getUsedSeats())->toBe(0);
        expect($this->licenseKey->getRemainingSeats())->toBe(0);
    });

    it('enforces unique license key constraint', function () {
        $licenseKey = LicenseKey::factory()->create();

        // Should fail when trying to create another license key with same key
        expect(fn () => LicenseKey::factory()->state(['key' => $licenseKey->key])->create())
            ->toThrow(Illuminate\Database\QueryException::class);
    });
});
