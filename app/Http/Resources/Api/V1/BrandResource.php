<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Brand Resource
 * 
 * Represents a brand in the multi-tenant ecosystem.
 * This resource provides comprehensive information about brands including
 * identification, domain information, and API access details.
 * 
 * @property int $id Database ID - Internal auto-incrementing identifier
 * @property string $uuid Unique identifier for the brand - Used in API endpoints and external references
 * @property string $name Human-readable brand name - Display name for the brand (e.g., "RankMath", "WP Rocket", "Imagify")
 * @property string $slug URL-friendly brand identifier - Used in URLs and API endpoints, must be unique across the system
 * @property string|null $domain Brand's primary domain - Main website domain for the brand (e.g., "rankmath.com", "wp-rocket.me")
 * @property string $api_key API key for brand authentication - Used in Authorization header for brand-facing API endpoints
 * @property bool $is_active Whether the brand is currently active - Controls whether the brand can use the API and create new resources
 * @property \Carbon\Carbon $created_at When the brand was created - ISO 8601 formatted timestamp
 * @property \Carbon\Carbon $updated_at When the brand was last updated - ISO 8601 formatted timestamp
 * @property \Illuminate\Database\Eloquent\Collection|null $products Associated products (when loaded) - Contains all products owned by this brand
 * @property \Illuminate\Database\Eloquent\Collection|null $license_keys Associated license keys (when loaded) - Contains all license keys owned by this brand
 */
class BrandResource extends JsonResource
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
            'domain' => $this->domain,
            'api_key' => $this->api_key,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
