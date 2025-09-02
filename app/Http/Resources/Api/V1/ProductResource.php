<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Resource
 * 
 * Represents a product within a brand that can be licensed.
 * This resource provides comprehensive information about products including
 * details, seat management capabilities, and status information.
 * 
 * @property int $id Database ID - Internal auto-incrementing identifier
 * @property string $uuid Unique identifier for the product - Used in API endpoints and external references
 * @property string $name Human-readable product name - Display name for the product (e.g., "RankMath SEO", "WP Rocket")
 * @property string $slug URL-friendly product identifier - Used in URLs and API endpoints, must be unique per brand
 * @property string|null $description Product description - Detailed information about the product's features and capabilities
 * @property int|null $max_seats Maximum number of seats allowed - Controls how many concurrent activations are allowed, null indicates no seat limit
 * @property bool $is_active Whether the product is currently active - Controls whether the product can be licensed and activated
 * @property \Carbon\Carbon $created_at When the product was created - ISO 8601 formatted timestamp
 * @property \Carbon\Carbon $updated_at When the product was last updated - ISO 8601 formatted timestamp
 * @property \App\Models\Brand|null $brand The associated brand (when loaded) - Contains brand information and API key
 * @property \Illuminate\Database\Eloquent\Collection|null $licenses Associated licenses (when loaded) - Contains all licenses for this product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'max_seats' => $this->max_seats,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
