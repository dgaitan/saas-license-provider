<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * License model representing individual licenses associated with products.
 *
 * This model implements multi-tenancy through its relationships to LicenseKey and Product,
 * both of which belong to specific brands. Licenses are indirectly isolated by brand
 * through these relationships.
 *
 * @property int $id
 * @property int $license_key_id
 * @property int $product_id
 * @property \App\Enums\LicenseStatus $status
 * @property \Carbon\Carbon $expires_at
 * @property int|null $max_seats
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\LicenseKey $licenseKey
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activation> $activations
 */
class License extends BaseApiModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'license_key_id',
        'product_id',
        'status',
        'expires_at',
        'max_seats',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => LicenseStatus::class,
        'expires_at' => 'datetime',
        'max_seats' => 'integer',
    ];

    /**
     * Get the license key that owns this license.
     */
    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }

    /**
     * Get the product associated with this license.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the activations for this license.
     */
    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    /**
     * Get active activations for this license.
     */
    public function activeActivations(): HasMany
    {
        return $this->activations()->where('status', \App\Enums\ActivationStatus::ACTIVE);
    }

    /**
     * Check if the license is valid.
     */
    public function isValid(): bool
    {
        return $this->status === LicenseStatus::VALID &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if the license supports seat management.
     */
    public function supportsSeats(): bool
    {
        return $this->max_seats !== null;
    }

    /**
     * Get the brand that owns this license through its license key.
     */
    public function getBrand(): ?Brand
    {
        return $this->licenseKey?->brand;
    }

    /**
     * Get the brand ID that owns this license through its license key.
     */
    public function getBrandId(): ?int
    {
        return $this->licenseKey?->brand_id;
    }

    /**
     * Check if this license belongs to the given brand.
     *
     * @param  \App\Models\Brand|int  $brand  The brand or brand ID to check
     */
    public function belongsToBrand(Brand|int $brand): bool
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return $this->getBrandId() === $brandId;
    }

    /**
     * Check if this license belongs to an active brand.
     */
    public function belongsToActiveBrand(): bool
    {
        return $this->licenseKey?->belongsToActiveBrand() ?? false;
    }

    /**
     * Scope to filter licenses by brand.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForBrand($query, Brand|int $brand): \Illuminate\Database\Eloquent\Builder
    {
        $brandId = $brand instanceof Brand ? $brand->id : $brand;

        return $query->whereHas('licenseKey', function ($licenseKeyQuery) use ($brandId) {
            $licenseKeyQuery->where('brand_id', $brandId);
        });
    }

    /**
     * Scope to filter licenses by active brands only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForActiveBrands($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('licenseKey.brand', function ($brandQuery) {
            $brandQuery->where('is_active', true);
        });
    }

    /**
     * Check if the license supports seat management.
     */
    public function supportsSeatManagement(): bool
    {
        return $this->supportsSeats();
    }

    /**
     * Get the number of used seats.
     */
    public function getUsedSeats(): int
    {
        return $this->activeActivations()->count();
    }

    /**
     * Get the number of remaining seats.
     */
    public function getRemainingSeats(): int
    {
        if (! $this->supportsSeats()) {
            return 0;
        }

        return max(0, $this->max_seats - $this->getUsedSeats());
    }

    /**
     * Get the number of available seats.
     */
    public function getAvailableSeats(): int
    {
        return $this->getRemainingSeats();
    }

    /**
     * Check if there are available seats.
     */
    public function hasAvailableSeats(): bool
    {
        if (! $this->supportsSeats()) {
            return true;
        }

        return $this->getRemainingSeats() > 0;
    }

    /**
     * Check if the license is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Renew the license by extending the expiration date.
     */
    public function renew(int $days = 365): void
    {
        $this->update([
            'expires_at' => $this->expires_at?->addDays($days) ?? now()->addDays($days),
            'status' => LicenseStatus::VALID,
        ]);
    }

    /**
     * Suspend the license.
     */
    public function suspend(): void
    {
        $this->update(['status' => LicenseStatus::SUSPENDED]);
    }

    /**
     * Resume the license.
     */
    public function resume(): void
    {
        $this->update(['status' => LicenseStatus::VALID]);
    }

    /**
     * Cancel the license.
     */
    public function cancel(): void
    {
        $this->update(['status' => LicenseStatus::CANCELLED]);
    }
}
