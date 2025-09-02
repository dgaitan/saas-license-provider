<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Activation Resource
 * 
 * Represents a license activation for a specific instance (site, machine, etc.)
 * 
 * @property int $id Database ID
 * @property string $uuid Unique identifier for the activation
 * @property string $instance_id Unique identifier for the instance (site URL, machine ID, etc.)
 * @property \App\Enums\ActivationStatus $status Current activation status
 * @property string $status_label Human-readable status label
 * @property \Carbon\Carbon|null $activated_at When the license was activated
 * @property \Carbon\Carbon|null $deactivated_at When the license was deactivated
 * @property \Carbon\Carbon $created_at When the activation record was created
 * @property \Carbon\Carbon $updated_at When the activation record was last updated
 * @property \App\Models\License|null $license The associated license (when loaded)
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
