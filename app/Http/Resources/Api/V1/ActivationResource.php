<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Activation Resource
 * 
 * Represents a license activation for a specific instance (site, machine, etc.).
 * This resource provides comprehensive information about license activations including
 * status, timing, and associated license details.
 * 
 * @property int $id Database ID - Internal auto-incrementing identifier
 * @property string $uuid Unique identifier for the activation - Used in API endpoints and external references
 * @property string $instance_id Unique identifier for the instance (site URL, machine ID, etc.) - Must be unique within the license
 * @property \App\Enums\ActivationStatus $status Current activation status - Controls whether the activation is consuming a seat
 * @property string $status_label Human-readable status label - User-friendly status description
 * @property \Carbon\Carbon|null $activated_at When the license was activated - ISO 8601 formatted timestamp
 * @property \Carbon\Carbon|null $deactivated_at When the license was deactivated - ISO 8601 formatted timestamp, null if still active
 * @property \Carbon\Carbon $created_at When the activation record was created - ISO 8601 formatted timestamp
 * @property \Carbon\Carbon $updated_at When the activation record was last updated - ISO 8601 formatted timestamp
 * @property \App\Models\License|null $license The associated license (when loaded) - Contains license details and product information
 */
class ActivationResource extends JsonResource
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
            'instance_id' => $this->instance_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'activated_at' => $this->activated_at?->toISOString(),
            'deactivated_at' => $this->deactivated_at?->toISOString(),
            'license' => new LicenseResource($this->whenLoaded('license')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
