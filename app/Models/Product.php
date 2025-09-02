<?php

namespace App\Models;

use App\Models\Traits\HasMultiTenancy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product model representing products within brands.
 *
 * This model implements multi-tenancy by belonging to a specific brand.
 * Each product is isolated to its brand and cannot be accessed by other brands.
 *
 * @property int $id
 * @property int $brand_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property int|null $max_seats
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Brand $brand
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\License> $licenses
 */
class Product extends BaseApiModel
{
    use HasMultiTenancy;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'description',
        'max_seats',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'max_seats' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the brand that owns this product.
     *
     * This relationship is provided by the HasMultiTenancy trait.
     * Each product belongs to exactly one brand.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the licenses associated with this product.
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * Check if the product supports seat management.
     */
    public function supportsSeats(): bool
    {
        return $this->max_seats !== null;
    }

    /**
     * Check if the product is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to get only active products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only products with seat management enabled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSeatManagement($query)
    {
        return $query->whereNotNull('max_seats');
    }

    /**
     * Scope to get only products without seat management.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutSeatManagement($query)
    {
        return $query->whereNull('max_seats');
    }

    /**
     * Get the total number of active licenses for this product.
     */
    public function getActiveLicensesCount(): int
    {
        return $this->licenses()->where('status', \App\Enums\LicenseStatus::VALID)->count();
    }

    /**
     * Get the total number of seats across all active licenses for this product.
     */
    public function getTotalSeatsCount(): int
    {
        return $this->licenses()
            ->where('status', \App\Enums\LicenseStatus::VALID)
            ->sum('max_seats');
    }
}
