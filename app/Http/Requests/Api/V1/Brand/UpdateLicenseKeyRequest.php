<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update License Key Request
 * 
 * Validates the request data for updating an existing license key.
 * This endpoint allows brands to modify license key properties.
 * 
 * @bodyParam customer_email string required The new customer email address. Must be a valid email format. Example: newcustomer@example.com
 * @bodyParam is_active boolean nullable Whether the license key should be active or inactive. Example: true
 */
class UpdateLicenseKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
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
            'customer_email' => 'required|email|max:255',
            'is_active' => 'nullable|boolean',
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
            'customer_email.required' => 'Customer email is required.',
            'customer_email.email' => 'Customer email must be a valid email address.',
            'customer_email.max' => 'Customer email may not be greater than 255 characters.',
            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }
}
