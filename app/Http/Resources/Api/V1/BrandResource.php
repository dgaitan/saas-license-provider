<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Brand Resource
 * 
 * Represents a brand in the multi-tenant ecosystem
 * 
 * @property int $id Database ID
 * @property string $uuid Unique identifier for the brand
 * @property string $name Human-readable brand name
 * @property string $slug URL-friendly brand identifier
 * @property string|null $domain Brand's primary domain
 * @property string $api_key API key for brand authentication
 * @property bool $is_active Whether the brand is currently active
 * @property \Carbon\Carbon $created_at When the brand was created
 * @property \Carbon\Carbon $updated_at When the brand was last updated
 * @property \Illuminate\Database\Eloquent\Collection|null $products Associated products (when loaded)
 * @property \Illuminate\Database\Eloquent\Collection|null $license_keys Associated license keys (when loaded)
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
