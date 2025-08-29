<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'brand_id' => $this->brand_id,
            'key' => $this->key,
            'customer_email' => $this->customer_email,
            'is_active' => $this->is_active,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'licenses' => LicenseResource::collection($this->whenLoaded('licenses')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
