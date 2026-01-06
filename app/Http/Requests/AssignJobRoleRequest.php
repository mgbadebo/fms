<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignJobRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->hasRole('ADMIN') || $user->can('users.assign_job_roles'));
    }

    public function rules(): array
    {
        return [
            'worker_job_role_id' => 'required|exists:worker_job_roles,id',
            'assigned_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
