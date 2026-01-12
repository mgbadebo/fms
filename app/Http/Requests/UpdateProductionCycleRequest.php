<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\GreenhouseProductionCycle;

class UpdateProductionCycleRequest extends FormRequest
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
        
        if (!$user->can('production_cycles.update')) {
            return false;
        }
        
        $cycle = GreenhouseProductionCycle::find($this->route('id'));
        if ($cycle && $cycle->farm_id) {
            return $user->farms()->where('farms.id', $cycle->farm_id)->exists();
        }
        
        return false;
    }

    public function rules(): array
    {
        return [
            'season_id' => 'sometimes|nullable|exists:seasons,id',
            'crop' => 'sometimes|string|in:BELL_PEPPER',
            'variety' => 'nullable|string|max:255',
            'cycle_status' => 'sometimes|in:PLANNED,ACTIVE,HARVESTING,COMPLETED,ABANDONED',
            'responsible_supervisor_user_id' => 'sometimes|exists:users,id',
            'planting_date' => [
                'sometimes',
                'date',
                function ($attribute, $value, $fail) {
                    $cycle = GreenhouseProductionCycle::find($this->route('id'));
                    if (!$cycle) {
                        return;
                    }
                    
                    // Get season_id from request or existing cycle
                    $seasonId = $this->input('season_id', $cycle->season_id);
                    
                    // If season_id is provided, validate planting_date >= season start_date
                    if ($seasonId) {
                        $season = \App\Models\Season::find($seasonId);
                        if ($season && $season->start_date) {
                            if (strtotime($value) < strtotime($season->start_date)) {
                                $fail('The planting date cannot be earlier than the season start date (' . $season->start_date->format('Y-m-d') . ').');
                            }
                        }
                    }
                },
            ],
            'notes' => 'nullable|string',
            // Allow partial updates - all fields optional on update
        ];
    }
}
