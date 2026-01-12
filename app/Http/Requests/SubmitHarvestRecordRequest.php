<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductionCycleHarvestRecord;

class SubmitHarvestRecordRequest extends FormRequest
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
        
        if (!$user->can('harvest.submit')) {
            return false;
        }
        
        // Must belong to farm
        return $user->farms()->where('farms.id', $record->farm_id)->exists();
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $record = ProductionCycleHarvestRecord::find($this->route('id'));
            if ($record) {
                // Must be DRAFT status
                if ($record->status !== 'DRAFT') {
                    $validator->errors()->add(
                        'status',
                        'Only DRAFT harvest records can be submitted.'
                    );
                }
                
                // Must have at least one crate
                if ($record->crates()->count() === 0) {
                    $validator->errors()->add(
                        'crates',
                        'Harvest record must have at least one crate before submission.'
                    );
                }
            }
        });
    }
}
