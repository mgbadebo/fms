<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_cycle_code' => $this->production_cycle_code,
            'crop' => $this->crop,
            'variety' => $this->variety,
            'cycle_status' => $this->cycle_status,
            
            // Section 1: Planting & Establishment
            'planting_date' => $this->planting_date?->format('Y-m-d'),
            'establishment_method' => $this->establishment_method,
            'seed_supplier_name' => $this->seed_supplier_name,
            'seed_batch_number' => $this->seed_batch_number,
            'nursery_start_date' => $this->nursery_start_date?->format('Y-m-d'),
            'transplant_date' => $this->transplant_date?->format('Y-m-d'),
            'plant_spacing_cm' => $this->plant_spacing_cm,
            'row_spacing_cm' => $this->row_spacing_cm,
            'plant_density_per_sqm' => $this->plant_density_per_sqm,
            'initial_plant_count' => $this->initial_plant_count,
            
            // Section 2: Growing medium & setup
            'cropping_system' => $this->cropping_system,
            'medium_type' => $this->medium_type,
            'bed_count' => $this->bed_count,
            'bench_count' => $this->bench_count,
            'mulching_used' => $this->mulching_used,
            'support_system' => $this->support_system,
            
            // Section 3: Environmental targets
            'target_day_temperature_c' => $this->target_day_temperature_c,
            'target_night_temperature_c' => $this->target_night_temperature_c,
            'target_humidity_percent' => $this->target_humidity_percent,
            'target_light_hours' => $this->target_light_hours,
            'ventilation_strategy' => $this->ventilation_strategy,
            'shade_net_percentage' => $this->shade_net_percentage,
            
            // Relationships
            'farm' => [
                'id' => $this->farm?->id,
                'name' => $this->farm?->name,
            ],
            'site' => [
                'id' => $this->site?->id,
                'name' => $this->site?->name,
            ],
            'greenhouse' => [
                'id' => $this->greenhouse?->id,
                'name' => $this->greenhouse?->name,
                'code' => $this->greenhouse?->greenhouse_code,
            ],
            'season' => $this->season ? [
                'id' => $this->season->id,
                'name' => $this->season->name,
            ] : null,
            'responsible_supervisor' => [
                'id' => $this->responsibleSupervisor?->id,
                'name' => $this->responsibleSupervisor?->name,
                'email' => $this->responsibleSupervisor?->email,
            ],
            
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
