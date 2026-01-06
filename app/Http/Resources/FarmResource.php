<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmResource extends JsonResource
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
            'farm_code' => $this->farm_code,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'farm_type' => $this->farm_type,
            'country' => $this->country,
            'state' => $this->state,
            'town' => $this->town,
            'default_currency' => $this->default_currency,
            'default_unit_system' => $this->default_unit_system,
            'default_timezone' => $this->default_timezone,
            'accounting_method' => $this->accounting_method,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
