<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\LicenseKey;
use App\Models\License;
use App\Models\Activation;
use App\Enums\ActivationStatus;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->product = Product::factory()->forBrand($this->brand)->create();
    $this->licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();
    $this->license = License::factory()
        ->forLicenseKey($this->licenseKey)
        ->forProduct($this->product)
        ->create();
    $this->activation = Activation::factory()->forLicense($this->license)->create();
});

describe('Activation Model', function () {
    it('can be created with factory', function () {
        expect($this->activation)->toBeInstanceOf(Activation::class);
        expect($this->activation->uuid)->toBeString();
        expect($this->activation->license_id)->toBe($this->license->id);
        expect($this->activation->instance_id)->toBeString();
        expect($this->activation->instance_type)->toBe('wordpress');
        expect($this->activation->instance_url)->toBeString();
        expect($this->activation->machine_id)->toBeString();
        expect($this->activation->status)->toBe(ActivationStatus::ACTIVE);
        expect($this->activation->activated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($this->activation->deactivated_at)->toBeNull();
    });

    it('belongs to a license', function () {
        expect($this->activation->license)->toBeInstanceOf(License::class);
        expect($this->activation->license->id)->toBe($this->license->id);
    });

    it('can be created with different statuses', function () {
        $deactivatedActivation = Activation::factory()->deactivated()->forLicense($this->license)->create();
        $expiredActivation = Activation::factory()->expired()->forLicense($this->license)->create();

        expect($deactivatedActivation->status)->toBe(ActivationStatus::DEACTIVATED);
        expect($expiredActivation->status)->toBe(ActivationStatus::EXPIRED);
    });

    it('can be created for specific instance', function () {
        $instanceId = 'test-instance-123';
        $instanceUrl = 'https://example.com';

        $activation = Activation::factory()
            ->forInstance($instanceId, $instanceUrl)
            ->forLicense($this->license)
            ->create();

        expect($activation->instance_id)->toBe($instanceId);
        expect($activation->instance_url)->toBe($instanceUrl);
    });

    it('can be created for WordPress site', function () {
        $siteUrl = 'https://mywordpresssite.com';

        $activation = Activation::factory()
            ->forWordPressSite($siteUrl)
            ->forLicense($this->license)
            ->create();

        expect($activation->instance_type)->toBe('wordpress');
        expect($activation->instance_url)->toBe($siteUrl);
        expect($activation->instance_id)->toBeString();
    });

    it('can be created for machine', function () {
        $machineId = 'machine-123';

        $activation = Activation::factory()
            ->forMachine($machineId)
            ->forLicense($this->license)
            ->create();

        expect($activation->instance_type)->toBe('machine');
        expect($activation->machine_id)->toBe($machineId);
        expect($activation->instance_id)->toBe($machineId);
    });

    it('checks if activation is active', function () {
        expect($this->activation->isActive())->toBeTrue();

        $deactivatedActivation = Activation::factory()->deactivated()->forLicense($this->license)->create();
        expect($deactivatedActivation->isActive())->toBeFalse();
    });

    it('can activate license for instance', function () {
        $activation = Activation::factory()->deactivated()->forLicense($this->license)->create();

        $activation->activate();

        expect($activation->status)->toBe(ActivationStatus::ACTIVE);
        expect($activation->activated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($activation->deactivated_at)->toBeNull();
    });

    it('can deactivate license for instance', function () {
        $this->activation->deactivate();

        expect($this->activation->status)->toBe(ActivationStatus::DEACTIVATED);
        expect($this->activation->deactivated_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('gets instance identifier correctly', function () {
        $activation = Activation::factory()->forLicense($this->license)->create([
            'instance_id' => 'test-id',
            'instance_url' => 'https://example.com',
            'machine_id' => 'machine-123',
        ]);

        expect($activation->getInstanceIdentifier())->toBe('test-id');

        $activation->instance_id = null;
        expect($activation->getInstanceIdentifier())->toBe('https://example.com');

        $activation->instance_url = null;
        expect($activation->getInstanceIdentifier())->toBe('machine-123');
    });

    it('can scope to active activations', function () {
        Activation::factory()->deactivated()->forLicense($this->license)->create();
        $activeActivation = Activation::factory()->active()->forLicense($this->license)->create();

        $activeActivations = Activation::active()->get();

        expect($activeActivations)->toHaveCount(2); // Including the one from beforeEach
        expect($activeActivations->pluck('status')->unique())->toContain(ActivationStatus::ACTIVE);
    });

    it('can scope to deactivated activations', function () {
        $deactivatedActivation = Activation::factory()->deactivated()->forLicense($this->license)->create();
        Activation::factory()->active()->forLicense($this->license)->create();

        $deactivatedActivations = Activation::deactivated()->get();

        expect($deactivatedActivations)->toHaveCount(1);
        expect($deactivatedActivations->first()->id)->toBe($deactivatedActivation->id);
    });

    it('enforces unique constraints for same instance', function () {
        $instanceId = 'unique-instance';

        Activation::factory()->forInstance($instanceId)->forLicense($this->license)->create();

        // Should fail when trying to create another activation for same instance and license
        expect(fn() => Activation::factory()->forInstance($instanceId)->forLicense($this->license)->create())
            ->toThrow(Illuminate\Database\QueryException::class);
    });

    it('allows same instance across different licenses', function () {
        $license2 = License::factory()->forLicenseKey($this->licenseKey)->forProduct($this->product)->create();
        $instanceId = 'shared-instance';

        $activation1 = Activation::factory()->forInstance($instanceId)->forLicense($this->license)->create();
        $activation2 = Activation::factory()->forInstance($instanceId)->forLicense($license2)->create();

        expect($activation1->instance_id)->toBe($activation2->instance_id);
        expect($activation1->license_id)->not->toBe($activation2->license_id);
    });
});
