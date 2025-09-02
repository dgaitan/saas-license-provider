<?php

namespace App\Http\Resources\Api\V1\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for listing license keys in brand-facing endpoints.
 *
 * This resource formats license key data when brands list their license keys.
 */
class LicenseKeyListResource extends JsonResource
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
            'key' => $this->key,
            'customer_email' => $this->customer_email,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'licenses_count' => $this->whenCounted('licenses'),
            'active_licenses_count' => $this->whenCounted('licenses', function () {
                return $this->licenses->where('status', 'valid')->count();
            }),
            'suspended_licenses_count' => $this->whenCounted('licenses', function () {
                return $this->licenses->where('status', 'suspended')->count();
            }),
            'cancelled_licenses_count' => $this->whenCounted('licenses', function () {
                return $this->licenses->where('status', 'cancelled')->count();
            }),
            'expired_licenses_count' => $this->whenCounted('licenses', function () {
                return $this->licenses->where('status', 'expired')->count();
            }),
        ];
    }
}
