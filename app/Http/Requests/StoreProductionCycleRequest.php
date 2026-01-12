<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Greenhouse;
use App\Models\Site;
use App\Models\Season;

class StoreProductionCycleRequest extends FormRequest
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
        
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('production_cycles.create')) {
            return false;
        }
        
        // Verify user belongs to the farm that owns the greenhouse
        $greenhouseId = $this->input('greenhouse_id');
        if ($greenhouseId) {
            $greenhouse = Greenhouse::find($greenhouseId);
            if ($greenhouse && $greenhouse->farm_id) {
                return $user->farms()->where('farms.id', $greenhouse->farm_id)->exists();
            }
        }
        
        return false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Reject farm_id and site_id if provided (auto-derived)
        if ($this->has('farm_id')) {
            $this->merge(['farm_id' => null]);
        }
        if ($this->has('site_id')) {
            $this->merge(['site_id' => null]);
        }
        if ($this->has('production_cycle_code')) {
            $this->merge(['production_cycle_code' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'greenhouse_id' => 'required|exists:greenhouses,id',
            'season_id' => 'nullable|exists:seasons,id',
            'crop' => 'required|string|in:BELL_PEPPER',
            'variety' => 'nullable|string|max:255',
            'responsible_supervisor_user_id' => 'required|exists:users,id',
            
            // Reject derived fields
            'farm_id' => 'prohibited',
            'site_id' => 'prohibited',
            'production_cycle_code' => 'prohibited',
            
            // Section 1: Planting & Establishment (all required)
            'planting_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    // If season_id is provided, validate planting_date >= season start_date
                    if ($this->has('season_id') && $this->season_id) {
                        $season = \App\Models\Season::find($this->season_id);
                        if ($season && $season->start_date) {
                            if (strtotime($value) < strtotime($season->start_date)) {
                                $fail('The planting date cannot be earlier than the season start date (' . $season->start_date->format('Y-m-d') . ').');
                            }
                        }
                    }
                },
            ],
            'establishment_method' => 'required|in:DIRECT_SEED,TRANSPLANT',
            'seed_supplier_name' => 'required|string|max:255',
            'seed_batch_number' => 'required|string|max:255',
            'nursery_start_date' => 'nullable|date|before_or_equal:planting_date',
            'transplant_date' => 'nullable|date|after_or_equal:nursery_start_date',
            'plant_spacing_cm' => 'required|numeric|min:0.1',
            'row_spacing_cm' => 'required|numeric|min:0.1',
            'plant_density_per_sqm' => 'nullable|numeric|min:0',
            'initial_plant_count' => 'required|integer|min:1',
            
            // Section 2: Growing medium & setup (all required)
            'cropping_system' => 'required|in:SOIL,COCOPEAT,HYDROPONIC',
            'medium_type' => 'required|string|max:255',
            'bed_count' => 'required|integer|min:1',
            'bench_count' => 'nullable|integer|min:0',
            'mulching_used' => 'required|boolean',
            'support_system' => 'required|in:STAKES,TRELLIS,STRING,NONE',
            
            // Section 3: Environmental targets (all required)
            'target_day_temperature_c' => 'required|numeric|min:-50|max:50',
            'target_night_temperature_c' => 'required|numeric|min:-50|max:50',
            'target_humidity_percent' => 'required|numeric|min:0|max:100',
            'target_light_hours' => 'required|numeric|min:0|max:24',
            'ventilation_strategy' => 'required|in:NATURAL,FORCED',
            'shade_net_percentage' => 'nullable|numeric|min:0|max:100',
            
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'greenhouse_id.required' => 'A greenhouse must be selected.',
            'greenhouse_id.exists' => 'The selected greenhouse does not exist.',
            'farm_id.prohibited' => 'farm_id cannot be set directly. It is derived from the greenhouse.',
            'site_id.prohibited' => 'site_id cannot be set directly. It is derived from the greenhouse.',
            'responsible_supervisor_user_id.required' => 'A responsible supervisor must be assigned.',
        ];
    }
}
