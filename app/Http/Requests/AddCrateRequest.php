<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductionCycleHarvestRecord;

class AddCrateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        $record = ProductionCycleHarvestRecord::find($this->route('harvest_record_id'));
        if (!$record) {
            return false;
        }
        
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.create')) {
            return false;
        }
        
        // Must belong to farm
        if (!$user->farms()->where('farms.id', $record->farm_id)->exists()) {
            return false;
        }
        
        // Only DRAFT records can have crates added (unless override permission)
        if ($record->status !== 'DRAFT' && !$user->can('harvest.override_status')) {
            return false;
        }
        
        return true;
    }

    public function rules(): array
    {
        return [
            'grade' => 'required|in:A,B,C',
            'crate_count' => 'nullable|integer|min:1|max:1000',
            'total_weight_kg' => 'required_without:weight_kg|nullable|numeric|min:0.01',
            'weight_kg' => 'required_without:total_weight_kg|nullable|numeric|min:0.01', // For backward compatibility
            'weighed_at' => 'nullable|date',
            'storage_location_id' => 'required|exists:inventory_locations,id',
            'label_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'crate_number' => 'prohibited', // Server assigns
            'farm_id' => 'prohibited',
        ];
    }
}
