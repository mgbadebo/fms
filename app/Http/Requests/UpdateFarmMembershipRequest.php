<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFarmMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->hasRole('ADMIN') || $user->can('users.manage_membership'));
    }

    public function rules(): array
    {
        return [
            'membership_status' => 'sometimes|in:ACTIVE,INACTIVE',
            'employment_category' => 'nullable|in:PERMANENT,CASUAL,CONTRACTOR,SEASONAL',
            'pay_type' => 'nullable|in:MONTHLY,DAILY,HOURLY,TASK',
            'pay_rate' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ];
    }
}
