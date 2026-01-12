<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductionCycleHarvestRecord;

class UpdateHarvestRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        $record = ProductionCycleHarvestRecord::find($this->route('id'));
        if (!$record) {
            return false;
        }
        
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.update')) {
            return false;
        }
        
        // Must belong to farm
        if (!$user->farms()->where('farms.id', $record->farm_id)->exists()) {
            return false;
        }
        
        // Only DRAFT records can be updated (unless override permission)
        if ($record->status !== 'DRAFT' && !$user->can('harvest.override_status')) {
            return false;
        }
        
        return true;
    }

    public function rules(): array
    {
        return [
            'harvest_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'production_cycle_id' => 'prohibited', // Cannot change cycle
            'farm_id' => 'prohibited',
            'site_id' => 'prohibited',
            'greenhouse_id' => 'prohibited',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $record = ProductionCycleHarvestRecord::find($this->route('id'));
            if (!$record) {
                return;
            }
            
            $harvestDate = $this->input('harvest_date');
            if ($harvestDate && $record->productionCycle && $record->productionCycle->planting_date) {
                $plantingDate = \Carbon\Carbon::parse($record->productionCycle->planting_date);
                $harvestDateCarbon = \Carbon\Carbon::parse($harvestDate);
                $daysDifference = $plantingDate->diffInDays($harvestDateCarbon, false);
                
                if ($daysDifference < 40) {
                    $validator->errors()->add(
                        'harvest_date',
                        'Harvest date must be at least 40 days after the planting date (' . $plantingDate->format('Y-m-d') . ').'
                    );
                }
            }
        });
    }
}
