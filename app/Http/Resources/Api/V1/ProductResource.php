<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Resource
 * 
 * Represents a product within a brand that can be licensed
 * 
 * @property int $id Database ID
 * @property string $uuid Unique identifier for the product
 * @property string $name Human-readable product name
 * @property string $slug URL-friendly product identifier
 * @property string|null $description Product description
 * @property int|null $max_seats Maximum number of seats allowed (null = no seat limit)
 * @property bool $is_active Whether the product is currently active
 * @property \Carbon\Carbon $created_at When the product was created
 * @property \Carbon\Carbon $updated_at When the product was last updated
 * @property \App\Models\Brand|null $brand The associated brand (when loaded)
 * @property \Illuminate\Database\Eloquent\Collection|null $licenses Associated licenses (when loaded)
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
