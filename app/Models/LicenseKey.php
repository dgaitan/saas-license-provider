<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LicenseKey model representing license keys that users receive.
 * 
 * @property int $id
 * @property int $brand_id
 * @property string $key
 * @property string $customer_email
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \App\Models\Brand $brand
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\License> $licenses
 */
class LicenseKey extends BaseApiModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'brand_id',
        'key',
        'customer_email',
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
     * Get the brand that owns this license key.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the licenses associated with this license key.
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * Get active licenses for this license key.
     */
    public function activeLicenses(): HasMany
    {
        return $this->licenses()->where('status', \App\Enums\LicenseStatus::VALID);
    }

    /**
     * Generate a unique license key.
     */
    public static function generateKey(): string
    {
        return str()->random(32);
    }

    /**
     * Check if the license key is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the total number of seats across all licenses.
     */
    public function getTotalSeats(): int
    {
        return $this->licenses()->sum('max_seats');
    }

    /**
     * Get the number of used seats across all licenses.
     */
    public function getUsedSeats(): int
    {
        return $this->licenses()
            ->with('activations')
            ->get()
            ->sum(function ($license) {
                return $license->activations()
                    ->where('status', \App\Enums\ActivationStatus::ACTIVE)
                    ->count();
            });
    }

    /**
     * Get the number of remaining seats.
     */
    public function getRemainingSeats(): int
    {
        return $this->getTotalSeats() - $this->getUsedSeats();
    }
}
