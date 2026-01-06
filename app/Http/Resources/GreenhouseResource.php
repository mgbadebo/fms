<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GreenhouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'greenhouse_code' => $this->greenhouse_code ?? $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status ?? ($this->is_active ? 'ACTIVE' : 'INACTIVE'),
            'site' => [
                'id' => $this->site?->id,
                'name' => $this->site?->name,
                'code' => $this->site?->code,
            ],
            'farm' => [
                'id' => $this->farm?->id,
                'name' => $this->farm?->name,
                'farm_code' => $this->farm?->farm_code,
            ],
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'total_area' => $this->total_area ?? ($this->length && $this->width ? $this->length * $this->width : null),
            'size_sqm' => $this->size_sqm,
            'orientation' => $this->orientation,
            'plant_capacity' => $this->plant_capacity,
            'primary_crop_type' => $this->primary_crop_type,
            'cropping_system' => $this->cropping_system,
            'kit_id' => $this->kit_id,
            'kit_number' => $this->kit_number,
            'built_date' => $this->built_date?->format('Y-m-d'),
            'construction_cost' => $this->construction_cost,
            'amortization_cycles' => $this->amortization_cycles,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
