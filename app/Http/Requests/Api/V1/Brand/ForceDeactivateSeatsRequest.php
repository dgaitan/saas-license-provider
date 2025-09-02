<?php

namespace App\Http\Requests\Api\V1\Brand;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Force Deactivate Seats Request
 * 
 * Validates the request data for forcefully deactivating license seats.
 * This endpoint allows brands to deactivate seats for administrative purposes.
 * 
 * @bodyParam reason string nullable The reason for force deactivation. Maximum 500 characters. Example: "Customer requested deactivation"
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
}
