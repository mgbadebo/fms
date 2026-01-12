<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\GreenhouseProductionCycle;

class StoreHarvestRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.create')) {
            return false;
        }
        
        // Verify user belongs to the farm of the production cycle
        $productionCycleId = $this->input('production_cycle_id');
        if ($productionCycleId) {
            $cycle = GreenhouseProductionCycle::find($productionCycleId);
            if ($cycle) {
                return $user->farms()->where('farms.id', $cycle->farm_id)->exists();
            }
        }
        
        return false;
    }

    public function rules(): array
    {
        return [
            'production_cycle_id' => 'required|exists:greenhouse_production_cycles,id',
            'harvest_date' => 'required|date',
            'notes' => 'nullable|string',
            // Reject client-supplied farm_id, site_id, greenhouse_id
            'farm_id' => 'prohibited',
            'site_id' => 'prohibited',
            'greenhouse_id' => 'prohibited',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productionCycleId = $this->input('production_cycle_id');
            if ($productionCycleId) {
                $cycle = GreenhouseProductionCycle::find($productionCycleId);
                if ($cycle) {
                    // Ensure cycle is ACTIVE or HARVESTING
                    if (!in_array($cycle->cycle_status, ['ACTIVE', 'HARVESTING'])) {
                        $validator->errors()->add(
                            'production_cycle_id',
                            'Production cycle must be ACTIVE or HARVESTING to record harvest.'
                        );
                    }
                    
                    // Check for duplicate harvest record for same cycle/date
                    $harvestDate = $this->input('harvest_date');
                    if ($harvestDate) {
                        $exists = \App\Models\ProductionCycleHarvestRecord::where('production_cycle_id', $productionCycleId)
                            ->where('harvest_date', $harvestDate)
                            ->exists();
                        
                        if ($exists) {
                            $validator->errors()->add(
                                'harvest_date',
                                'A harvest record already exists for this production cycle on this date.'
                            );
                        }
                        
                        // Validate harvest date is at least 40 days after planting date
                        if ($cycle->planting_date) {
                            $plantingDate = \Carbon\Carbon::parse($cycle->planting_date);
                            $harvestDateCarbon = \Carbon\Carbon::parse($harvestDate);
                            $daysDifference = $plantingDate->diffInDays($harvestDateCarbon, false);
                            
                            if ($daysDifference < 40) {
                                $validator->errors()->add(
                                    'harvest_date',
                                    'Harvest date must be at least 40 days after the planting date (' . $plantingDate->format('Y-m-d') . ').'
                                );
                            }
                        }
                    }
                }
            }
        });
    }
}
