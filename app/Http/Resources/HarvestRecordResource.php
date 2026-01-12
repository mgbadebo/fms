<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'harvest_date' => $this->harvest_date?->format('Y-m-d'),
            'status' => $this->status,
            'production_cycle' => $this->productionCycle ? [
                'id' => $this->productionCycle->id,
                'production_cycle_code' => $this->productionCycle->production_cycle_code,
                'crop' => $this->productionCycle->crop,
                'variety' => $this->productionCycle->variety,
            ] : null,
            'greenhouse' => $this->greenhouse ? [
                'id' => $this->greenhouse->id,
                'name' => $this->greenhouse->name,
            ] : null,
            'totals' => [
                'a_kg' => $this->total_weight_kg_a,
                'b_kg' => $this->total_weight_kg_b,
                'c_kg' => $this->total_weight_kg_c,
                'total_kg' => $this->total_weight_kg_total,
                'crate_count_a' => $this->crate_count_a,
                'crate_count_b' => $this->crate_count_b,
                'crate_count_c' => $this->crate_count_c,
                'crate_count_total' => $this->crate_count_total,
            ],
            'crates' => $this->whenLoaded('crates', function () {
                return $this->crates->map(function ($crate) {
                    return [
                        'id' => $crate->id,
                        'grade' => $crate->grade,
                        'crate_number' => $crate->crate_number,
                        'weight_kg' => $crate->weight_kg,
                        'weighed_at' => $crate->weighed_at?->toISOString(),
                        'weighed_by' => $crate->weigher ? [
                            'id' => $crate->weigher->id,
                            'name' => $crate->weigher->name,
                        ] : null,
                        'label_code' => $crate->label_code,
                        'notes' => $crate->notes,
                    ];
                });
            }),
            'recorded_by' => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ] : null,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_by' => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null,
            'approved_at' => $this->approved_at?->toISOString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
