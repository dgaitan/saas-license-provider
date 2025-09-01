<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
