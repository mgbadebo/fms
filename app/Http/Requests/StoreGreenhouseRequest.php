<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Site;

class StoreGreenhouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission and belongs to the farm that owns the site
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        // Check if user has greenhouses.create permission
        if (!$user->can('greenhouses.create')) {
            return false;
        }
        
        // Verify user belongs to the farm that owns the site
        $siteId = $this->input('site_id');
        if ($siteId) {
            $site = Site::find($siteId);
            if ($site && $site->farm_id) {
                // Check if user belongs to this farm
                return $user->farms()->where('farms.id', $site->farm_id)->exists();
            }
        }
        
        return false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Reject farm_id if provided
        if ($this->has('farm_id')) {
            $this->merge(['farm_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = $this->input('site_id');
        
        return [
            'site_id' => 'required|exists:sites,id',
            'greenhouse_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('greenhouses', 'greenhouse_code')->where(function ($query) use ($siteId) {
                    return $query->where('site_id', $siteId);
                }),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:TUNNEL,GLASSHOUSE,POLYHOUSE,SHADE_HOUSE',
            'status' => 'required|in:ACTIVE,INACTIVE,MAINTENANCE,DECOMMISSIONED',
            'length' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'height' => 'nullable|numeric|min:0',
            'orientation' => 'nullable|in:N_S,E_W,NE_SW,NW_SE',
            'plant_capacity' => 'nullable|integer|min:0',
            'primary_crop_type' => 'nullable|string|max:100',
            'cropping_system' => 'nullable|in:SOIL,COCOPEAT,HYDROPONIC',
            'notes' => 'nullable|string',
            // Reject farm_id explicitly
            'farm_id' => 'prohibited',
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
            'site_id.required' => 'A site must be selected.',
            'site_id.exists' => 'The selected site does not exist.',
            'greenhouse_code.unique' => 'This greenhouse code already exists for this site.',
            'farm_id.prohibited' => 'farm_id cannot be set directly. It is derived from the selected site.',
        ];
    }
}
