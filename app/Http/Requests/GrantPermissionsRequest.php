<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->hasRole('ADMIN') || $user->can('permissions.manage'));
    }

    public function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|exists:permissions,name',
        ];
    }
}
