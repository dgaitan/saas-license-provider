<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * License Key Resource
 * 
 * Represents a license key that can unlock multiple licenses for a customer
 * 
 * @property int $id Database ID
 * @property string $uuid Unique identifier for the license key
 * @property string $key The actual license key string
 * @property string $customer_email Customer's email address
 * @property bool $is_active Whether the license key is currently active
 * @property \Carbon\Carbon $created_at When the license key was created
 * @property \Carbon\Carbon $updated_at When the license key was last updated
 * @property \App\Models\Brand|null $brand The associated brand (when loaded)
 * @property \Illuminate\Database\Eloquent\Collection|null $licenses Associated licenses (when loaded)
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
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'key' => $this->key,
            'customer_email' => $this->customer_email,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
