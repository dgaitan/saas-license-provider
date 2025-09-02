<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store License Request
 * 
 * Validates the request data for creating a new license.
 * This endpoint allows brands to provision licenses for their customers.
 * 
 * **Authentication Required**: This endpoint requires brand authentication using the `Authorization: Bearer {BRAND_API_KEY}` header.
 * The brand API key is automatically generated when a brand is created and can be found in the brands table.
 * 
 * @bodyParam license_key_uuid string required The UUID of the license key to associate with this license. Must exist in the license_keys table and belong to the authenticated brand. Example: "550e8400-e29b-41d4-a716-446655440000"
 * @bodyParam product_uuid string required The UUID of the product this license grants access to. Must exist in the products table and belong to the authenticated brand. Example: "6ba7b810-9dad-11d1-80b4-00c04fd430c8"
 * @bodyParam expires_at string nullable The expiration date for the license. Must be a valid date in the future. If not provided, the license will never expire. Format: YYYY-MM-DD or ISO 8601. Example: "2026-12-31" or "2026-12-31T23:59:59Z"
 * @bodyParam max_seats integer nullable The maximum number of seats allowed for this license. Must be at least 1. If not provided, the license will use the product's default seat limit. Example: 5
 */
class StoreLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Brand authentication is handled by the auth.brand middleware
        // which validates the Authorization: Bearer {BRAND_API_KEY} header
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'license_key_uuid' => 'required|string|exists:license_keys,uuid',
            'product_uuid' => 'required|string|exists:products,uuid',
            'expires_at' => 'nullable|date|after:now',
            'max_seats' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_key_uuid.required' => 'License key UUID is required.',
            'license_key_uuid.exists' => 'License key not found.',
            'product_uuid.required' => 'Product UUID is required.',
            'product_uuid.exists' => 'Product not found.',
            'expires_at.date' => 'Expiration date must be a valid date.',
            'expires_at.after' => 'Expiration date must be in the future.',
            'max_seats.integer' => 'Maximum seats must be a number.',
            'max_seats.min' => 'Maximum seats must be at least 1.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'license_key_uuid' => 'license key UUID',
            'product_uuid' => 'product UUID',
            'expires_at' => 'expiration date',
            'max_seats' => 'maximum seats',
        ];
    }
}
