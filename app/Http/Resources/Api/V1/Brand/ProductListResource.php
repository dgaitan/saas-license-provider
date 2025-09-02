<?php

namespace App\Http\Resources\Api\V1\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product List Resource for Brand-facing Endpoints
 * 
 * This resource formats product data when brands list their products.
 * Provides a comprehensive view of products with associated license information.
 * 
 * @property string $uuid Unique identifier for the product
 * @property string $name Human-readable product name
 * @property string $slug URL-friendly product identifier
 * @property string|null $description Product description
 * @property int|null $max_seats Maximum number of seats allowed (null = no seat limit)
 * @property bool $is_active Whether the product is currently active
 * @property \Carbon\Carbon $created_at When the product was created
 * @property \Carbon\Carbon $updated_at When the product was last updated
 * @property \Illuminate\Database\Eloquent\Collection|null $licenses Associated licenses (when loaded)
 * @property int|null $licenses_count Total number of licenses (when counted)
 */
class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
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
