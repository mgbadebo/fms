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
            'asset_id' => $this->asset_id,
            'asset' => $this->asset ? [
                'id' => $this->asset->id,
                'asset_category_id' => $this->asset->asset_category_id,
                'asset_code' => $this->asset->asset_code,
                'name' => $this->asset->name,
                'description' => $this->asset->description,
                'status' => $this->asset->status,
                'acquisition_type' => $this->asset->acquisition_type,
                'purchase_date' => $this->asset->purchase_date?->format('Y-m-d'),
                'purchase_cost' => $this->asset->purchase_cost,
                'currency' => $this->asset->currency,
                'supplier_name' => $this->asset->supplier_name,
                'serial_number' => $this->asset->serial_number,
                'model' => $this->asset->model,
                'manufacturer' => $this->asset->manufacturer,
                'year_of_make' => $this->asset->year_of_make,
                'warranty_expiry' => $this->asset->warranty_expiry?->format('Y-m-d'),
                'is_trackable' => $this->asset->is_trackable,
            ] : null,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
