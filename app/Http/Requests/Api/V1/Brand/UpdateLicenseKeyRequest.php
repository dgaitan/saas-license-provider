<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update License Key Request
 * 
 * Validates the request data for updating an existing license key.
 * This endpoint allows brands to modify license key properties.
 * 
 * **Authentication Required**: This endpoint requires brand authentication using the `Authorization: Bearer {BRAND_API_KEY}` header.
 * The brand API key is automatically generated when a brand is created and can be found in the brands table.
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
