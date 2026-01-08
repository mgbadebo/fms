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
        // Reject farm_id and greenhouse_code if provided (auto-generated/derived)
        if ($this->has('farm_id')) {
            $this->merge(['farm_id' => null]);
        }
        if ($this->has('greenhouse_code')) {
            $this->merge(['greenhouse_code' => null]);
        }
        if ($this->has('asset_id')) {
            $this->merge(['asset_id' => null]);
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
        $trackAsAsset = $this->boolean('track_as_asset', false);
        
        $rules = [
            'site_id' => 'required|exists:sites,id',
            'greenhouse_code' => [
                'nullable',
                'prohibited', // Auto-generated, not allowed from client
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
            // Asset tracking checkbox
            'track_as_asset' => ['boolean'],
        ];
        
        // If tracking as asset, add asset field validation
        if ($trackAsAsset) {
            $rules = array_merge($rules, [
                'asset_category_id' => ['nullable','exists:asset_categories,id'],
                'asset_description' => ['nullable','string'],
                'asset_acquisition_type' => ['nullable','in:PURCHASED,LEASED,RENTED,DONATED'],
                'asset_purchase_date' => ['nullable','date'],
                'asset_purchase_cost' => ['nullable','numeric','min:0'],
                'asset_currency' => ['nullable','string','size:3'],
                'asset_supplier_name' => ['nullable','string','max:255'],
                'asset_serial_number' => ['nullable','string','max:255'],
                'asset_model' => ['nullable','string','max:255'],
                'asset_manufacturer' => ['nullable','string','max:255'],
                'asset_year_of_make' => ['nullable','integer','min:1900','max:' . (date('Y') + 1)],
                'asset_warranty_expiry' => ['nullable','date'],
                'asset_is_trackable' => ['boolean'],
            ]);
        }
        
        return $rules;
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
