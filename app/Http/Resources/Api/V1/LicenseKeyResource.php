<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * License Key Resource
 * 
 * Represents a license key that can unlock multiple licenses for a customer.
 * This resource provides comprehensive information about license keys including
 * customer details, status, and associated licenses.
 * 
 * @property int $id Database ID - Internal auto-incrementing identifier
 * @property string $uuid Unique identifier for the license key - Used in API endpoints and external references
 * @property string $key The actual license key string - Unique alphanumeric string used for license activation
 * @property string $customer_email Customer's email address - Used to associate all licenses for this customer across brands
 * @property bool $is_active Whether the license key is currently active - Controls whether the key can be used for new activations
 * @property \Carbon\Carbon $created_at When the license key was created - ISO 8601 formatted timestamp
 * @property \Carbon\Carbon $updated_at When the license key was last updated - ISO 8601 formatted timestamp
 * @property \App\Models\Brand|null $brand The associated brand (when loaded) - Contains brand information and API key
 * @property \Illuminate\Database\Eloquent\Collection|null $licenses Associated licenses (when loaded) - Contains all licenses associated with this key
 */
class LicenseKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'key' => $this->key,
            'customer_email' => $this->customer_email,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Include brand_id if the brand relationship is loaded
        if ($this->relationLoaded('brand')) {
            $data['brand_id'] = $this->brand_id;
        }

        // Include licenses if the licenses relationship is loaded
        if ($this->relationLoaded('licenses')) {
            $data['licenses'] = $this->licenses;
        }

        return $data;
    }
}
