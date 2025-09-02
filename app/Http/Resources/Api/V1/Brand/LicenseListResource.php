<?php

namespace App\Http\Resources\Api\V1\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * License List Resource for Brand-facing Endpoints
 * 
 * This resource formats license data when brands list their licenses.
 * Provides a comprehensive view of licenses with associated product and license key information.
 * 
 * @property string $uuid Unique identifier for the license
 * @property \App\Enums\LicenseStatus $status Current license status (valid, suspended, cancelled, expired)
 * @property \Carbon\Carbon|null $expires_at When the license expires (null = never expires)
 * @property int|null $max_seats Maximum number of seats allowed (null = no seat limit)
 * @property \Carbon\Carbon $created_at When the license was created
 * @property \Carbon\Carbon $updated_at When the license was last updated
 * @property \App\Models\Product $product The associated product
 * @property \App\Models\LicenseKey $license_key The associated license key
 * @property int|null $activations_count Total number of activations (when counted)
 * @property int|null $active_activations_count Number of currently active activations (when counted)
 */
class LicenseListResource extends JsonResource
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
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'max_seats' => $this->max_seats,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'product' => [
                'uuid' => $this->product->uuid,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'max_seats' => $this->product->max_seats,
            ],
            'license_key' => [
                'uuid' => $this->licenseKey->uuid,
                'key' => $this->licenseKey->key,
                'customer_email' => $this->licenseKey->customer_email,
                'is_active' => $this->licenseKey->is_active,
            ],
            'activations_count' => $this->whenCounted('activations'),
            'active_activations_count' => $this->whenCounted('activations', function () {
                return $this->activations->where('status', 'active')->count();
            }),
        ];
    }
}
