<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Site;
use App\Models\Borehole;

class UpdateBoreholeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;
        if ($user->hasRole('ADMIN')) return true;
        $borehole = $this->route('borehole');
        if (!$borehole instanceof Borehole) {
            $borehole = Borehole::find($borehole);
        }
        if (!$borehole) return false;
        return $user->can('boreholes.update') && $user->farms()->where('farms.id', $borehole->farm_id)->exists();
    }

    protected function prepareForValidation(): void
    {
        // Prohibit farm_id and borehole_code from client (auto-generated/derived)
        if ($this->has('farm_id')) {
            $this->merge(['farm_id' => null]);
        }
        if ($this->has('borehole_code')) {
            $this->merge(['borehole_code' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_id' => ['sometimes','exists:sites,id'],
            'borehole_code' => ['nullable','prohibited'], // Auto-generated, not allowed from client
            'name' => ['sometimes','string','max:255'],
            'status' => ['sometimes','in:ACTIVE,INACTIVE,UNDER_REPAIR,DECOMMISSIONED'],
            'gps_lat' => ['nullable','numeric'],
            'gps_lng' => ['nullable','numeric'],
            'location_description' => ['nullable','string','max:255'],
            'depth_m' => ['nullable','numeric','min:0'],
            'static_water_level_m' => ['nullable','numeric','min:0'],
            'yield_m3_per_hr' => ['nullable','numeric','min:0'],
            'casing_diameter_mm' => ['nullable','integer','min:0'],
            'drilling_date' => ['nullable','date'],
            'drilling_contractor' => ['nullable','string','max:255'],
            'borehole_type' => ['nullable','in:HAND_PUMP,MOTOR_PUMP,SOLAR_PUMP,SOURCE_ONLY'],
            'is_metered' => ['boolean'],
            'meter_reference' => ['nullable','string','max:100'],
            'next_water_test_due_at' => ['nullable','date'],
            'water_quality_notes' => ['nullable','string'],
            'asset_id' => ['nullable','exists:assets,id'],
            'pump_asset_id' => ['nullable','exists:assets,id'],
            'power_asset_id' => ['nullable','exists:assets,id'],
            'storage_tank_asset_id' => ['nullable','exists:assets,id'],
            'notes' => ['nullable','string'],
            'farm_id' => ['prohibited'],
            // Asset tracking
            'track_as_asset' => ['boolean'],
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
        ];
    }
}
