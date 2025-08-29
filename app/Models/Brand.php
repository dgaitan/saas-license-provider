<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Brand model representing multi-tenant brands in the license service.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $domain
 * @property string $api_key
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LicenseKey> $licenseKeys
 */
class Brand extends BaseApiModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'api_key',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the products associated with this brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the license keys associated with this brand.
     */
    public function licenseKeys(): HasMany
    {
        return $this->hasMany(LicenseKey::class);
    }

    /**
     * Generate a unique API key for the brand.
     */
    public static function generateApiKey(): string
    {
        return 'brand_'.str()->random(32);
    }

    /**
     * Check if the brand is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
