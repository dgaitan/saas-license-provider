<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Force Deactivate Seats Request
 * 
 * Validates the request data for forcefully deactivating license seats.
 * This endpoint allows brands to deactivate seats for administrative purposes.
 * 
 * **Authentication Required**: This endpoint requires brand authentication using the `X-Tenant: {BRAND_API_KEY}` header.
 * The brand API key is automatically generated when a brand is created and can be found in the brands table.
 * 
 * @bodyParam reason string nullable The reason for force deactivation. This field helps track why seats were forcefully deactivated, which is useful for support, compliance, and audit purposes. Maximum 500 characters. Example: "Customer requested deactivation", "Policy violation detected", "Account suspended", "License expired", "Administrative action"
 */
class ForceDeactivateSeatsRequest extends FormRequest
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
            'reason.string' => 'Reason must be a string.',
            'reason.max' => 'Reason may not be greater than 500 characters.',
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
            'reason' => 'deactivation reason',
        ];
    }
}
