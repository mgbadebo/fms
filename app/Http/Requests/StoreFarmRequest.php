<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users with OWNER or ADMIN role can create farms
        $user = $this->user();
        return $user && ($user->hasRole('ADMIN') || $user->hasRole('OWNER'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'farm_type' => 'required|in:CROP,LIVESTOCK,MIXED,AQUACULTURE,HORTICULTURE',
            'country' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'town' => 'required|string|max:100',
            'default_currency' => 'required|string|size:3',
            'default_unit_system' => 'required|in:METRIC,IMPERIAL',
            'default_timezone' => 'required|timezone',
            'accounting_method' => 'required|in:CASH,ACCRUAL',
            'status' => 'required|in:ACTIVE,INACTIVE,ARCHIVED',
        ];
    }
}
