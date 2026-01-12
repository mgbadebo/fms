<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionCycleAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_cycle_id' => $this->production_cycle_id,
            'log_date' => $this->log_date?->format('Y-m-d'),
            'alert_type' => $this->alert_type,
            'message' => $this->message,
            'severity' => $this->severity,
            'is_resolved' => $this->is_resolved,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'resolved_by' => $this->resolvedBy ? [
                'id' => $this->resolvedBy->id,
                'name' => $this->resolvedBy->name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
