<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestCrateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'harvest_record_id' => $this->harvest_record_id,
            'grade' => $this->grade,
            'crate_number' => $this->crate_number,
            'weight_kg' => $this->weight_kg,
            'weighed_at' => $this->weighed_at?->toISOString(),
            'weighed_by' => $this->weigher ? [
                'id' => $this->weigher->id,
                'name' => $this->weigher->name,
            ] : null,
            'label_code' => $this->label_code,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
