<?php

namespace App\Models;

use App\Enums\ActivationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activation model representing license activations for specific instances.
 * 
 * @property int $id
 * @property int $license_id
 * @property string $instance_id
 * @property string $instance_type
 * @property string $instance_url
 * @property string $machine_id
 * @property \App\Enums\ActivationStatus $status
 * @property \Carbon\Carbon $activated_at
 * @property \Carbon\Carbon|null $deactivated_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \App\Models\License $license
 */
class Activation extends BaseApiModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'license_id',
        'instance_id',
        'instance_type',
        'instance_url',
        'machine_id',
        'status',
        'activated_at',
        'deactivated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ActivationStatus::class,
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    /**
     * Get the license that owns this activation.
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Check if the activation is active.
     */
    public function isActive(): bool
    {
        return $this->status === ActivationStatus::ACTIVE;
    }

    /**
     * Activate the license for this instance.
     */
    public function activate(): void
    {
        $this->update([
            'status' => ActivationStatus::ACTIVE,
            'activated_at' => now(),
            'deactivated_at' => null,
        ]);
    }

    /**
     * Deactivate the license for this instance.
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => ActivationStatus::DEACTIVATED,
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Get the instance identifier.
     */
    public function getInstanceIdentifier(): string
    {
        return $this->instance_id ?: $this->instance_url ?: $this->machine_id;
    }

    /**
     * Scope to get active activations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', ActivationStatus::ACTIVE);
    }

    /**
     * Scope to get deactivated activations.
     */
    public function scopeDeactivated($query)
    {
        return $query->where('status', ActivationStatus::DEACTIVATED);
    }
}
