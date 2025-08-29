<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\LicenseKey;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
});

describe('Brand Model', function () {
    it('can be created with factory', function () {
        expect($this->brand)->toBeInstanceOf(Brand::class);
        expect($this->brand->uuid)->toBeString();
        expect($this->brand->name)->toBeString();
        expect($this->brand->slug)->toBeString();
        expect($this->brand->api_key)->toBeString();
        expect($this->brand->is_active)->toBeTrue();
    });

    it('generates unique API keys', function () {
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();

        expect($brand1->api_key)->not->toBe($brand2->api_key);
        expect(strlen($brand1->api_key))->toBeGreaterThan(30);
        expect($brand1->api_key)->toStartWith('brand_');
    });

    it('can be marked as inactive', function () {
        $inactiveBrand = Brand::factory()->inactive()->create();

        expect($inactiveBrand->is_active)->toBeFalse();
        expect($inactiveBrand->isActive())->toBeFalse();
    });

    it('can be created with specific name', function () {
        $brand = Brand::factory()->withName('Test Brand')->create();

        expect($brand->name)->toBe('Test Brand');
        expect($brand->slug)->toBe('test-brand');
    });

    it('has products relationship', function () {
        $product = Product::factory()->forBrand($this->brand)->create();

        expect($this->brand->products)->toHaveCount(1);
        expect($this->brand->products->first())->toBeInstanceOf(Product::class);
        expect($this->brand->products->first()->id)->toBe($product->id);
    });

    it('has license keys relationship', function () {
        $licenseKey = LicenseKey::factory()->forBrand($this->brand)->create();

        expect($this->brand->licenseKeys)->toHaveCount(1);
        expect($this->brand->licenseKeys->first())->toBeInstanceOf(LicenseKey::class);
        expect($this->brand->licenseKeys->first()->id)->toBe($licenseKey->id);
    });

    it('can scope to active brands', function () {
        Brand::factory()->inactive()->create();
        $activeBrand = Brand::factory()->create();

        $activeBrands = Brand::active()->get();

        expect($activeBrands)->toHaveCount(2); // Including the one from beforeEach
        expect($activeBrands->pluck('is_active')->unique())->toContain(true);
    });

    it('has unique constraints', function () {
        $brand = Brand::factory()->create();

        // Should fail when trying to create another brand with same slug
        expect(fn() => Brand::factory()->withName($brand->name)->create())
            ->toThrow(Illuminate\Database\QueryException::class);
    });
});
