<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model for API models with common functionality.
 */
abstract class BaseApiModel extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the model's API representation.
     */
    public function toApiArray(): array
    {
        return $this->toArray();
    }

    /**
     * Scope to get active records.
     */
    public function scopeActive($query)
    {
        if (in_array('is_active', $this->fillable)) {
            return $query->where('is_active', true);
        }

        return $query;
    }

    /**
     * Check if the model is active.
     */
    public function isActive(): bool
    {
        if (in_array('is_active', $this->fillable)) {
            return (bool) $this->is_active;
        }

        return true;
    }
}
