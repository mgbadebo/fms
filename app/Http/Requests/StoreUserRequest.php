<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->hasRole('ADMIN') || $user->can('users.create'));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Decode JSON strings from FormData
        if ($this->has('farms') && is_string($this->input('farms'))) {
            $this->merge([
                'farms' => json_decode($this->input('farms'), true) ?? [],
            ]);
        }

        if ($this->has('permissions') && is_string($this->input('permissions'))) {
            $this->merge([
                'permissions' => json_decode($this->input('permissions'), true) ?? [],
            ]);
        }

        if ($this->has('job_roles') && is_string($this->input('job_roles'))) {
            $this->merge([
                'job_roles' => json_decode($this->input('job_roles'), true) ?? [],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072', // 3MB max
            
            // Farm memberships (optional)
            'farms' => 'nullable|array',
            'farms.*.farm_id' => 'required_with:farms|exists:farms,id',
            'farms.*.membership_status' => 'required_with:farms|in:ACTIVE,INACTIVE',
            'farms.*.employment_category' => 'nullable|in:PERMANENT,CASUAL,CONTRACTOR,SEASONAL',
            'farms.*.pay_type' => 'nullable|in:MONTHLY,DAILY,HOURLY,TASK',
            'farms.*.pay_rate' => 'nullable|numeric|min:0',
            'farms.*.start_date' => 'nullable|date',
            'farms.*.end_date' => 'nullable|date|after:farms.*.start_date',
            'farms.*.notes' => 'nullable|string',
            
            // Permissions (optional)
            'permissions' => 'nullable|array',
            'permissions.*' => 'required|string|exists:permissions,name',
            
            // Job roles (optional, farm-scoped)
            'job_roles' => 'nullable|array',
            'job_roles.*.farm_id' => 'required_with:job_roles|exists:farms,id',
            'job_roles.*.worker_job_role_id' => 'required_with:job_roles|exists:worker_job_roles,id',
            'job_roles.*.assigned_at' => 'nullable|date',
            'job_roles.*.notes' => 'nullable|string',
        ];
    }
}
