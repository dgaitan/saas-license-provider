<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product model representing products within brands.
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
}
