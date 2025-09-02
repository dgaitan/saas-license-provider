<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Renew License Request
 * 
 * Validates the request data for renewing an existing license.
 * This endpoint allows brands to extend license expiration dates.
 * 
 * @bodyParam expires_at string required The new expiration date for the license. Must be a valid date in the future. Example: 2027-12-31
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
}
