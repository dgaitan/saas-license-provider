<?php

namespace App\Http\Resources\Api\V1\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for listing licenses in brand-facing endpoints.
 *
 * This resource formats license data when brands list their licenses.
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
