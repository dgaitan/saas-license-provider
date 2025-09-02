<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Activate License Request
 * 
 * Validates the request data for activating a license for a specific instance.
 * This endpoint allows end-user products to activate licenses for their instances.
 * 
 * @bodyParam instance_id string required A unique identifier for the instance (site URL, machine ID, etc.). Maximum 255 characters. Example: "site-123" or "machine-abc"
 * @bodyParam instance_type string required The type of instance being activated. Must be one of: wordpress, machine, cli, app. Example: "wordpress"
 * @bodyParam instance_url string nullable The URL of the instance (for web-based products). Must be a valid URL. Maximum 500 characters. Example: "https://example.com"
 * @bodyParam machine_id string nullable The machine identifier (for desktop/CLI applications). Maximum 255 characters. Example: "MAC-ABC123"
 */
class ActivateLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // TODO: Implement product authentication via Bearer token
        // For now, allow all requests
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
            'instance_type' => 'required|string|in:wordpress,machine,cli,app',
            'instance_url' => 'nullable|url|max:500',
            'machine_id' => 'nullable|string|max:255',
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
            'instance_type.required' => 'Instance type is required.',
            'instance_type.string' => 'Instance type must be a string.',
            'instance_type.in' => 'Instance type must be one of: wordpress, machine, cli, app.',
            'instance_url.url' => 'Instance URL must be a valid URL.',
            'instance_url.max' => 'Instance URL may not be greater than 500 characters.',
            'machine_id.string' => 'Machine ID must be a string.',
            'machine_id.max' => 'Machine ID may not be greater than 255 characters.',
        ];
    }
}
