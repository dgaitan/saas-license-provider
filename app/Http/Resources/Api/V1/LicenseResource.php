<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * License Resource
 * 
 * Represents a license that grants access to a specific product
 * 
 * @property int $id Database ID
 * @property string $uuid Unique identifier for the license
 * @property int $license_key_id Associated license key ID
 * @property int $product_id Associated product ID
 * @property \App\Enums\LicenseStatus $status Current license status (valid, suspended, cancelled, expired)
 * @property string $status_label Human-readable status label
 * @property \Carbon\Carbon|null $expires_at When the license expires (null = never expires)
 * @property int|null $max_seats Maximum number of seats allowed (null = no seat limit)
 * @property \Carbon\Carbon $created_at When the license was created
 * @property \Carbon\Carbon $updated_at When the license was last updated
 * @property \App\Models\Product|null $product The associated product (when loaded)
 * @property \App\Models\LicenseKey|null $license_key The associated license key (when loaded)
 */
class LicenseResource extends JsonResource
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
