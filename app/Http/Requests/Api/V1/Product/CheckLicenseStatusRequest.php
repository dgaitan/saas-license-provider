<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Check License Status Request
 * 
 * Validates the request data for checking license status and entitlements.
 * This endpoint allows end-user products to validate licenses and check seat availability.
 * 
 * @bodyParam license_key string required The license key to check. Must be a valid license key string. Example: "LK-ABC123-DEF456"
 * @bodyParam instance_id string nullable The instance ID to check against (for seat validation). Maximum 255 characters. Example: "site-123"
 */
class CheckLicenseStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // This is a public endpoint - no authentication required
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
            'license_key' => 'required|string|max:255',
            'instance_id' => 'nullable|string|max:255',
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
            'license_key.required' => 'License key is required.',
            'license_key.string' => 'License key must be a string.',
            'license_key.max' => 'License key may not be greater than 255 characters.',
            'instance_id.string' => 'Instance ID must be a string.',
            'instance_id.max' => 'Instance ID may not be greater than 255 characters.',
        ];
    }
}
