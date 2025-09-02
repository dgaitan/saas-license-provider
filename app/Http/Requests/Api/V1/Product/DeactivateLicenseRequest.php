<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deactivate License Request
 * 
 * Validates the request data for deactivating a license for a specific instance.
 * This endpoint allows end-user products to deactivate licenses, freeing up seats.
 * 
 * **Authentication**: This endpoint does not require authentication. It is designed for end-user products
 * to deactivate licenses without needing to authenticate with the system.
 * 
 * @bodyParam instance_id string required The unique identifier for the instance to deactivate. Must match the instance_id used during activation. Example: "site-123"
 * @bodyParam reason string nullable The reason for deactivation (optional). Maximum 500 characters. Example: "Site migration completed"
 */
class DeactivateLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // This endpoint is public and does not require authentication
        // It is designed for end-user products to deactivate licenses
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
            'instance_id' => 'required|string|max:255',
            'reason' => 'nullable|string|max:500',
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
            'instance_id.required' => 'Instance ID is required.',
            'instance_id.string' => 'Instance ID must be a string.',
            'instance_id.max' => 'Instance ID may not be greater than 255 characters.',
            'reason.string' => 'Reason must be a string.',
            'reason.max' => 'Reason may not be greater than 500 characters.',
        ];
    }
}
