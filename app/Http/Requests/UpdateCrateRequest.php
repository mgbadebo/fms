<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductionCycleHarvestCrate;

class UpdateCrateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        $crate = ProductionCycleHarvestCrate::find($this->route('id'));
        if (!$crate) {
            return false;
        }
        
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.update')) {
            return false;
        }
        
        // Must belong to farm
        if (!$user->farms()->where('farms.id', $crate->farm_id)->exists()) {
            return false;
        }
        
        // Only DRAFT harvest records can have crates updated (unless override permission)
        if ($crate->harvestRecord && $crate->harvestRecord->status !== 'DRAFT' && !$user->can('harvest.override_status')) {
            return false;
        }
        
        return true;
    }

    public function rules(): array
    {
        return [
            'grade' => 'sometimes|in:A,B,C',
            'weight_kg' => 'sometimes|numeric|min:0.01',
            'weighed_at' => 'nullable|date',
            'storage_location_id' => 'required|exists:inventory_locations,id',
            'label_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'crate_number' => 'prohibited', // Cannot change
            'harvest_record_id' => 'prohibited', // Cannot change
            'farm_id' => 'prohibited',
        ];
    }
}
