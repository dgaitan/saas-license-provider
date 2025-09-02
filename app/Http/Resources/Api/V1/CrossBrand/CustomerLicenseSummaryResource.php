<?php

namespace App\Http\Resources\Api\V1\CrossBrand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for customer license summary in cross-brand operations.
 *
 * This resource formats license key and license data when brands
 * query customer information across all brands.
 */
class CustomerLicenseSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'uuid' => $this->uuid,
            'key' => $this->key,
            'customer_email' => $this->customer_email,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Include brand information if loaded
        if ($this->relationLoaded('brand')) {
            $data['brand'] = [
                'uuid' => $this->brand->uuid,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'domain' => $this->brand->domain,
            ];
        }

        // Include licenses information if loaded
        if ($this->relationLoaded('licenses')) {
            $data['licenses'] = $this->licenses->map(function ($license) {
                $licenseData = [
                    'uuid' => $license->uuid,
                    'status' => $license->status,
                    'expires_at' => $license->expires_at?->toISOString(),
                    'max_seats' => $license->max_seats,
                    'created_at' => $license->created_at?->toISOString(),
                ];

                // Include product information if loaded
                if ($license->relationLoaded('product')) {
                    $licenseData['product'] = [
                        'uuid' => $license->product->uuid,
                        'name' => $license->product->name,
                        'slug' => $license->product->slug,
                        'description' => $license->product->description,
                        'max_seats' => $license->product->max_seats,
                    ];
                }

                // Include activations information if loaded
                if ($license->relationLoaded('activations')) {
                    $licenseData['activations'] = [
                        'total' => $license->activations->count(),
                        'active' => $license->activations->where('status', 'active')->count(),
                        'deactivated' => $license->activations->where('status', 'deactivated')->count(),
                        'expired' => $license->activations->where('status', 'expired')->count(),
                    ];
                }

                return $licenseData;
            });
        }

        return $data;
    }
}
