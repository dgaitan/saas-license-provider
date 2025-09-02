<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Renew License Request
 * 
 * Validates the request data for renewing an existing license.
 * This endpoint allows brands to extend license expiration dates.
 * 
 * **Authentication Required**: This endpoint requires brand authentication using the `Authorization: Bearer {BRAND_API_KEY}` header.
 * The brand API key is automatically generated when a brand is created and can be found in the brands table.
 * 
 * @bodyParam expires_at string required The new expiration date for the license. Must be a valid date in the future. This will replace the current expiration date and extend the license validity period. Format: YYYY-MM-DD or ISO 8601 datetime. Example: "2027-12-31", "2027-12-31T23:59:59Z", "2028-01-01"
 */
class RenewLicenseRequest extends FormRequest
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
            'expires_at' => 'required|date|after:now',
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
            'expires_at.required' => 'Expiration date is required.',
            'expires_at.date' => 'Expiration date must be a valid date.',
            'expires_at.after' => 'Expiration date must be in the future.',
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
            'expires_at' => 'expiration date',
        ];
    }
}
