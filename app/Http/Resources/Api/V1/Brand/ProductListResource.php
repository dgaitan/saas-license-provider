<?php

namespace App\Http\Resources\Api\V1\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for listing products in brand-facing endpoints.
 *
 * This resource formats product data when brands list their products.
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
            'licenses_count' => $this->whenCounted('licenses'),
            'active_licenses_count' => $this->whenCounted('licenses', function () {
                return $this->licenses->where('status', 'valid')->count();
            }),
        ];
    }
}
