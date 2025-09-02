<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * License Resource
 * 
 * Represents a license that grants access to a specific product.
 * This resource provides comprehensive information about licenses including
 * status, expiration, seat management, and associated entities.
 * 
 * @property int $id Database ID - Internal auto-incrementing identifier
 * @property string $uuid Unique identifier for the license - Used in API endpoints and external references
 * @property int $license_key_id Associated license key ID - Links to the customer's license key
 * @property int $product_id Associated product ID - Links to the specific product being licensed
 * @property \App\Enums\LicenseStatus $status Current license status - Controls whether the license can be activated (valid, suspended, cancelled, expired)
 * @property string $status_label Human-readable status label - User-friendly status description for display purposes
 * @property \Carbon\Carbon|null $expires_at When the license expires - ISO 8601 formatted timestamp, null indicates never expires
 * @property int|null $max_seats Maximum number of seats allowed - Controls concurrent activations, null indicates no seat limit
 * @property \Carbon\Carbon $created_at When the license was created - ISO 8601 formatted timestamp
 * @property \Carbon\Carbon $updated_at When the license was last updated - ISO 8601 formatted timestamp
 * @property \App\Models\Product|null $product The associated product (when loaded) - Contains product details and brand information
 * @property \App\Models\LicenseKey|null $license_key The associated license key (when loaded) - Contains customer and brand information
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
