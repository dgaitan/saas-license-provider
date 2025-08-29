<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
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
