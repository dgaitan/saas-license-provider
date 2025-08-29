<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

class StoreLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Implement brand authentication via Bearer token
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
}
