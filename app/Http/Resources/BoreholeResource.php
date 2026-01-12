<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoreholeResource extends JsonResource
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
            'borehole_code' => $this->borehole_code ?? $this->code,
            'name' => $this->name,
            'status' => $this->status ?? ($this->is_active ? 'ACTIVE' : 'INACTIVE'),
            'site' => [
                'id' => $this->site?->id,
                'name' => $this->site?->name,
            ],
            'farm' => [
                'id' => $this->farm?->id,
                'name' => $this->farm?->name,
            ],
            'gps_lat' => $this->gps_lat,
            'gps_lng' => $this->gps_lng,
            'location_description' => $this->location_description,
            'depth_m' => $this->depth_m,
            'static_water_level_m' => $this->static_water_level_m,
            'yield_m3_per_hr' => $this->yield_m3_per_hr,
            'casing_diameter_mm' => $this->casing_diameter_mm,
            'drilling_date' => $this->drilling_date?->format('Y-m-d'),
            'drilling_contractor' => $this->drilling_contractor,
            'borehole_type' => $this->borehole_type,
            'is_metered' => (bool)$this->is_metered,
            'meter_reference' => $this->meter_reference,
            'next_water_test_due_at' => $this->next_water_test_due_at?->format('Y-m-d'),
            'water_quality_notes' => $this->water_quality_notes,
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
            'pump_asset_id' => $this->pump_asset_id,
            'power_asset_id' => $this->power_asset_id,
            'storage_tank_asset_id' => $this->storage_tank_asset_id,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}


