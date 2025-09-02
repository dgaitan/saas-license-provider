<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Renew License Request
 * 
 * Validates the request data for renewing an existing license.
 * This endpoint allows brands to extend license expiration dates.
 * 
 * **Authentication Required**: This endpoint requires brand authentication using the `X-Tenant: {BRAND_API_KEY}` header.
 * The brand API key is automatically generated when a brand is created and can be found in the brands table.
 * 
 * @bodyParam days integer nullable The number of days to extend the license by. Must be a positive integer. If provided, this will be used to calculate the new expiration date. Example: 365, 730, 30
 * @bodyParam expires_at string nullable The new expiration date for the license. Must be a valid date in the future. This will replace the current expiration date and extend the license validity period. If both days and expires_at are provided, expires_at takes precedence. Format: YYYY-MM-DD or ISO 8601 datetime. Example: "2027-12-31", "2027-12-31T23:59:59Z", "2028-01-01"
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
            'days' => 'nullable|integer|min:1|max:3650', // Max 10 years
            'expires_at' => 'nullable|date|after:now',
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
            'days.integer' => 'Days must be a whole number.',
            'days.min' => 'Days must be at least 1.',
            'days.max' => 'Days cannot exceed 3650 (10 years).',
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
            'days' => 'days',
            'expires_at' => 'expiration date',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // If neither days nor expires_at is provided, that's fine - we'll use default 365 days
            // Only validate if both are provided (then expires_at takes precedence)
            if ($this->has('days') && $this->has('expires_at')) {
                // Both provided - expires_at takes precedence, so we can ignore days
                // No validation error needed
            }
        });
    }
}
