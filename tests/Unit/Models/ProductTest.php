<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\License;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->product = Product::factory()->forBrand($this->brand)->create();
});

describe('Product Model', function () {
    it('can be created with factory', function () {
        expect($this->product)->toBeInstanceOf(Product::class);
        expect($this->product->uuid)->toBeString();
        expect($this->product->name)->toBeString();
        expect($this->product->slug)->toBeString();
        expect($this->product->brand_id)->toBe($this->brand->id);
        expect($this->product->is_active)->toBeTrue();
    });

    it('belongs to a brand', function () {
        expect($this->product->brand)->toBeInstanceOf(Brand::class);
        expect($this->product->brand->id)->toBe($this->brand->id);
    });

    it('has licenses relationship', function () {
        $license = License::factory()->forProduct($this->product)->create();

        expect($this->product->licenses)->toHaveCount(1);
        expect($this->product->licenses->first())->toBeInstanceOf(License::class);
        expect($this->product->licenses->first()->id)->toBe($license->id);
    });

    it('can be created with seat management', function () {
        $productWithSeats = Product::factory()->withSeats(5)->forBrand($this->brand)->create();

        expect($productWithSeats->max_seats)->toBe(5);
        expect($productWithSeats->supportsSeats())->toBeTrue();
    });

    it('can be created without seat management', function () {
        $productWithoutSeats = Product::factory()->withoutSeats()->forBrand($this->brand)->create();

        expect($productWithoutSeats->max_seats)->toBeNull();
        expect($productWithoutSeats->supportsSeats())->toBeFalse();
    });

    it('can be marked as inactive', function () {
        $inactiveProduct = Product::factory()->inactive()->forBrand($this->brand)->create();

        expect($inactiveProduct->is_active)->toBeFalse();
        expect($inactiveProduct->isActive())->toBeFalse();
    });

    it('can scope to active products', function () {
        Product::factory()->inactive()->forBrand($this->brand)->create();
        $activeProduct = Product::factory()->forBrand($this->brand)->create();

        $activeProducts = Product::active()->get();

        expect($activeProducts)->toHaveCount(2); // Including the one from beforeEach
        expect($activeProducts->pluck('is_active')->unique())->toContain(true);
    });

    it('enforces unique slug per brand', function () {
        $product = Product::factory()->forBrand($this->brand)->create();

        // Should fail when trying to create another product with same slug in same brand
        expect(fn() => Product::factory()->withName($product->name)->forBrand($this->brand)->create())
            ->toThrow(Illuminate\Database\QueryException::class);
    });

    it('allows same slug across different brands', function () {
        $brand2 = Brand::factory()->create();
        $product1 = Product::factory()->forBrand($this->brand)->create();
        $product2 = Product::factory()->withName($product1->name)->forBrand($brand2)->create();

        expect($product1->slug)->toBe($product2->slug);
        expect($product1->brand_id)->not->toBe($product2->brand_id);
    });
});
