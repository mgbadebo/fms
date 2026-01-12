<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductionCycleDailyLog;

class UpdateDailyLogRequest extends FormRequest
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
        
        $log = ProductionCycleDailyLog::find($this->route('id'));
        if (!$log) {
            return false;
        }
        
        // Can only update DRAFT logs
        if ($log->status !== 'DRAFT') {
            return false;
        }
        
        if (!$user->can('daily_logs.update')) {
            return false;
        }
        
        if ($log->farm_id) {
            return $user->farms()->where('farms.id', $log->farm_id)->exists();
        }
        
        return false;
    }

    public function rules(): array
    {
        // Same as StoreDailyLogRequest but all fields optional
        return [
            'log_date' => 'sometimes|date',
            'issues_notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.activity_type_id' => 'required_with:items|exists:activity_types,id',
            'items.*.performed_by_user_id' => 'nullable|exists:users,id',
            'items.*.started_at' => 'nullable|date',
            'items.*.ended_at' => 'nullable|date|after:items.*.started_at',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',
            'items.*.meta' => 'nullable|array',
            'items.*.inputs' => 'nullable|array',
            'items.*.inputs.*.input_item_id' => 'nullable|exists:input_items,id',
            'items.*.inputs.*.input_name' => 'nullable|string|max:255',
            'items.*.inputs.*.quantity' => 'required_with:items.*.inputs|numeric|min:0',
            'items.*.inputs.*.unit' => 'required_with:items.*.inputs|string|max:50',
            'items.*.inputs.*.notes' => 'nullable|string',
        ];
    }
}
