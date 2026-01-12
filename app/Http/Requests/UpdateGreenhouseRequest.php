<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Site;
use App\Models\Greenhouse;

class UpdateGreenhouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        // Check if user has greenhouses.update permission
        if (!$user->can('greenhouses.update')) {
            return false;
        }
        
        // Get the greenhouse being updated
        $greenhouse = $this->route('greenhouse');
        if ($greenhouse instanceof Greenhouse) {
            // Verify user belongs to the farm that owns this greenhouse
            return $user->farms()->where('farms.id', $greenhouse->farm_id)->exists();
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
        $greenhouse = $this->route('greenhouse');
        $greenhouseId = $greenhouse instanceof Greenhouse ? $greenhouse->id : $greenhouse;
        $siteId = $this->input('site_id', $greenhouse instanceof Greenhouse ? $greenhouse->site_id : null);
        
        return [
            'site_id' => [
                'sometimes',
                'exists:sites,id',
                // If site changes, ensure user has access to new site's farm
                function ($attribute, $value, $fail) use ($greenhouse) {
                    if ($value && $greenhouse instanceof Greenhouse) {
                        $newSite = Site::find($value);
                        $user = $this->user();
                        if ($newSite && $newSite->farm_id) {
                            if (!$user->farms()->where('farms.id', $newSite->farm_id)->exists()) {
                                $fail('You do not have access to the farm that owns the selected site.');
                            }
                        }
                    }
                },
            ],
            'greenhouse_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('greenhouses', 'greenhouse_code')
                    ->where(function ($query) use ($siteId, $greenhouseId) {
                        return $query->where('site_id', $siteId)
                                     ->where('id', '!=', $greenhouseId);
                    }),
            ],
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:TUNNEL,GLASSHOUSE,POLYHOUSE,SHADE_HOUSE',
            'status' => 'sometimes|in:ACTIVE,INACTIVE,MAINTENANCE,DECOMMISSIONED',
            'length' => 'sometimes|numeric|min:0.1',
            'width' => 'sometimes|numeric|min:0.1',
            'height' => 'nullable|numeric|min:0',
            'orientation' => 'nullable|in:N_S,E_W,NE_SW,NW_SE',
            'plant_capacity' => 'nullable|integer|min:0',
            'primary_crop_type' => 'nullable|string|max:100',
            'cropping_system' => 'nullable|in:SOIL,COCOPEAT,HYDROPONIC',
            'notes' => 'nullable|string',
            // Reject farm_id explicitly
            'farm_id' => 'prohibited',
            // Asset tracking
            'track_as_asset' => 'boolean',
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'asset_description' => 'nullable|string',
            'asset_acquisition_type' => 'nullable|in:PURCHASED,LEASED,RENTED,DONATED',
            'asset_purchase_date' => 'nullable|date',
            'asset_purchase_cost' => 'nullable|numeric|min:0',
            'asset_currency' => 'nullable|string|max:3',
            'asset_supplier_name' => 'nullable|string|max:255',
            'asset_serial_number' => 'nullable|string|max:255',
            'asset_model' => 'nullable|string|max:255',
            'asset_manufacturer' => 'nullable|string|max:255',
            'asset_year_of_make' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'asset_warranty_expiry' => 'nullable|date',
            'asset_is_trackable' => 'boolean',
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
            'site_id.exists' => 'The selected site does not exist.',
            'greenhouse_code.unique' => 'This greenhouse code already exists for this site.',
            'farm_id.prohibited' => 'farm_id cannot be set directly. It is derived from the selected site.',
        ];
    }
}
