<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
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
            'license_key_id' => $this->license_key_id,
            'product_id' => $this->product_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'expires_at' => $this->expires_at?->toISOString(),
            'max_seats' => $this->max_seats,

            'product' => new ProductResource($this->whenLoaded('product')),
            'license_key' => new LicenseKeyResource($this->whenLoaded('licenseKey')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
