<?php

namespace App\Http\Resources\Api\V1\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * License Key List Resource for Brand-facing Endpoints
 * 
 * This resource formats license key data when brands list their license keys.
 * Provides a comprehensive view of license keys with associated license information.
 * 
 * @property string $uuid Unique identifier for the license key
 * @property string $key The actual license key string
 * @property string $customer_email Customer's email address
 * @property bool $is_active Whether the license key is currently active
 * @property \Carbon\Carbon $created_at When the license key was created
 * @property \Carbon\Carbon $updated_at When the license key was last updated
 * @property \Illuminate\Database\Eloquent\Collection|null $licenses Associated licenses (when loaded)
 * @property int|null $licenses_count Total number of licenses (when counted)
 * @property int|null $active_licenses_count Number of currently active licenses (when counted)
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
        ];
    }
}
