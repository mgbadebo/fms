<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductionCycleHarvestRecord;

class ApproveHarvestRecordRequest extends FormRequest
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
        
        if (!$user->can('harvest.approve')) {
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
                // Must be SUBMITTED status
                if ($record->status !== 'SUBMITTED') {
                    $validator->errors()->add(
                        'status',
                        'Only SUBMITTED harvest records can be approved.'
                    );
                }
            }
        });
    }
}
