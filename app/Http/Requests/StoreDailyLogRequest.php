<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\GreenhouseProductionCycle;
use App\Models\ActivityType;

class StoreDailyLogRequest extends FormRequest
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
        
        if (!$user->can('daily_logs.create')) {
            return false;
        }
        
        // Verify user belongs to the farm that owns the production cycle
        $cycleId = $this->route('production_cycle_id') ?? $this->input('production_cycle_id');
        if ($cycleId) {
            $cycle = GreenhouseProductionCycle::find($cycleId);
            if ($cycle && $cycle->farm_id) {
                return $user->farms()->where('farms.id', $cycle->farm_id)->exists();
            }
        }
        
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'log_date' => 'required|date',
            'issues_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.activity_type_id' => 'required|exists:activity_types,id',
            'items.*.performed_by_user_id' => 'nullable|exists:users,id',
            'items.*.started_at' => 'nullable|date',
            'items.*.ended_at' => 'nullable|date|after:items.*.started_at',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',
            'items.*.meta' => 'nullable|array',
            'items.*.inputs' => 'nullable|array',
            'items.*.inputs.*.input_item_id' => 'nullable|exists:input_items,id',
            'items.*.inputs.*.input_name' => 'nullable|string|max:255|required_without:items.*.inputs.*.input_item_id',
            'items.*.inputs.*.quantity' => 'required|numeric|min:0',
            'items.*.inputs.*.unit' => 'required|string|max:50',
            'items.*.inputs.*.notes' => 'nullable|string',
        ];

        // Add type-specific validation after basic rules
        if ($this->has('items')) {
            foreach ($this->input('items', []) as $index => $item) {
                if (isset($item['activity_type_id'])) {
                    $activityType = ActivityType::find($item['activity_type_id']);
                    if ($activityType) {
                        $rules = array_merge($rules, $this->getTypeSpecificRules($activityType, $index));
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Get type-specific validation rules based on activity type code.
     */
    protected function getTypeSpecificRules(ActivityType $activityType, int $index): array
    {
        $rules = [];
        $code = $activityType->code;

        switch ($code) {
            case 'IRRIGATION':
                // Requires started_at/ended_at OR quantity+unit (at least one)
                $rules["items.{$index}.started_at"] = 'required_without_all:items.' . $index . '.quantity';
                $rules["items.{$index}.ended_at"] = 'required_with:items.' . $index . '.started_at';
                $rules["items.{$index}.quantity"] = 'required_without_all:items.' . $index . '.started_at|numeric|min:0';
                $rules["items.{$index}.unit"] = 'required_with:items.' . $index . '.quantity|in:L,m3,minutes';
                break;

            case 'FERTIGATION':
                // Requires quantity+unit AND at least one input
                $rules["items.{$index}.quantity"] = 'required|numeric|min:0';
                $rules["items.{$index}.unit"] = 'required|string|in:L,m3';
                $rules["items.{$index}.inputs"] = 'required|array|min:1';
                break;

            case 'SPRAYING':
                // Requires time range and at least one input; notes for target
                $rules["items.{$index}.started_at"] = 'required|date';
                $rules["items.{$index}.ended_at"] = 'required|date|after:items.' . $index . '.started_at';
                $rules["items.{$index}.inputs"] = 'required|array|min:1';
                $rules["items.{$index}.notes"] = 'required|string|min:10'; // Must describe target pest/disease
                break;

            case 'SCOUTING':
                // Meta fields required when pests/disease observed
                $rules["items.{$index}.meta.pests_observed"] = 'nullable|boolean';
                $rules["items.{$index}.meta.disease_observed"] = 'nullable|boolean';
                $rules["items.{$index}.meta.severity"] = 'required_if:items.' . $index . '.meta.pests_observed,true|required_if:items.' . $index . '.meta.disease_observed,true|in:LOW,MEDIUM,HIGH';
                break;

            case 'CLEANING_SANITATION':
                // Requires notes or checklist meta
                $rules["items.{$index}.notes"] = 'required_without:items.' . $index . '.meta.checklist';
                $rules["items.{$index}.meta.checklist"] = 'required_without:items.' . $index . '.notes|array';
                break;

            case 'OTHER':
                // Notes required
                $rules["items.{$index}.notes"] = 'required|string|min:5';
                break;

            default:
                // For PRUNING, TRELLISING, DELEAFING: time range recommended but not required
                if (in_array($code, ['PRUNING', 'TRELLISING', 'DELEAFING'])) {
                    $rules["items.{$index}.started_at"] = 'nullable|date';
                    $rules["items.{$index}.ended_at"] = 'nullable|date|after:items.' . $index . '.started_at';
                }
                break;
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure all activity types belong to the same farm as the production cycle
            $cycleId = $this->route('production_cycle_id') ?? $this->input('production_cycle_id');
            if ($cycleId && $this->has('items')) {
                $cycle = GreenhouseProductionCycle::find($cycleId);
                if ($cycle) {
                    foreach ($this->input('items', []) as $index => $item) {
                        if (isset($item['activity_type_id'])) {
                            $activityType = ActivityType::find($item['activity_type_id']);
                            if ($activityType && $activityType->farm_id !== $cycle->farm_id) {
                                $validator->errors()->add(
                                    "items.{$index}.activity_type_id",
                                    "Activity type must belong to the same farm as the production cycle."
                                );
                            }
                        }
                    }
                }
            }
        });
    }
}
