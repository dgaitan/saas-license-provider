<?php

namespace App\Models\Traits;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait to provide multi-tenancy functionality for models that belong to brands.
 *
 * This trait provides methods and scopes to ensure proper brand isolation
 * and multi-tenant data access patterns.
 */
trait HasMultiTenancy
{
    /**
     * Boot the trait and add global scopes.
     */
    protected static function bootHasMultiTenancy(): void
    {
        // Add global scope to ensure brand isolation in queries
        static::addGlobalScope('brand', function (Builder $builder) {
            // Only apply scope if we have an authenticated brand context
            if (app()->bound('authenticated_brand')) {
                $brand = app('authenticated_brand');
                $builder->where('brand_id', $brand->id);
            }
        });
    }

    /**
     * Get the brand that owns this model.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Scope to filter by brand.
     *
     * @param  Builder  $query  The query builder
     * @param  Brand|int  $brand  The brand or brand ID
     */
    public function scopeForBrand(Builder $query, Brand|int $brand): Builder
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope to filter by active brands only.
     *
     * @param  Builder  $query  The query builder
     */
    public function scopeForActiveBrands(Builder $query): Builder
    {
        return $query->whereHas('brand', function (Builder $brandQuery) {
            $brandQuery->where('is_active', true);
        });
    }

    /**
     * Check if this model belongs to the given brand.
     *
     * @param  Brand|int  $brand  The brand or brand ID to check
     */
    public function belongsToBrand(Brand|int $brand): bool
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return $this->brand_id === $brandId;
    }

    /**
     * Check if this model belongs to an active brand.
     */
    public function belongsToActiveBrand(): bool
    {
        return $this->brand && $this->brand->isActive();
    }

    /**
     * Get the brand ID for this model.
     */
    public function getBrandId(): int
    {
        return $this->brand_id;
    }

    /**
     * Get the brand name for this model.
     */
    public function getBrandName(): ?string
    {
        return $this->brand?->name;
    }

    /**
     * Get the brand slug for this model.
     */
    public function getBrandSlug(): ?string
    {
        return $this->brand?->slug;
    }
}
