<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store License Key Request
 * 
 * Validates the request data for creating a new license key.
 * This endpoint allows brands to generate license keys for their customers.
 * 
 * @bodyParam customer_email string required The customer's email address. Must be a valid email format. Example: customer@example.com
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
}
