<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store License Key Request
 * 
 * Validates the request data for creating a new license key.
 * This endpoint allows brands to generate license keys for their customers.
 * 
 * **Authentication Required**: This endpoint requires brand authentication using the `X-Tenant: {BRAND_API_KEY}` header.
 * The brand API key is automatically generated when a brand is created and can be found in the brands table.
 * 
 * @bodyParam customer_email string required The customer's email address. Must be a valid email format and will be used to associate all licenses for this customer. Maximum 255 characters. Example: "customer@example.com" or "john.doe@company.org"
 */
class StoreLicenseKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Brand authentication is handled by the auth.brand middleware
        // which validates the X-Tenant: {BRAND_API_KEY} header
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
            'customer_email' => 'customer email',
        ];
    }
}
