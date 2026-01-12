<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_date' => $this->log_date?->format('Y-m-d'),
            'production_cycle_id' => $this->production_cycle_id,
            'production_cycle' => $this->productionCycle ? [
                'id' => $this->productionCycle->id,
                'production_cycle_code' => $this->productionCycle->production_cycle_code,
                'cycle_status' => $this->productionCycle->cycle_status,
            ] : null,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'submitted_by' => $this->submittedBy ? [
                'id' => $this->submittedBy->id,
                'name' => $this->submittedBy->name,
            ] : null,
            'issues_notes' => $this->issues_notes,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'activity_type' => [
                        'id' => $item->activityType->id,
                        'code' => $item->activityType->code,
                        'name' => $item->activityType->name,
                    ],
                    'performed_by' => $item->performedBy ? [
                        'id' => $item->performedBy->id,
                        'name' => $item->performedBy->name,
                    ] : null,
                    'started_at' => $item->started_at?->toISOString(),
                    'ended_at' => $item->ended_at?->toISOString(),
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'notes' => $item->notes,
                    'meta' => $item->meta,
                    'inputs' => $item->inputs->map(function ($input) {
                        return [
                            'id' => $input->id,
                            'input_item' => $input->inputItem ? [
                                'id' => $input->inputItem->id,
                                'name' => $input->inputItem->name,
                            ] : null,
                            'input_name' => $input->input_name,
                            'quantity' => $input->quantity,
                            'unit' => $input->unit,
                            'notes' => $input->notes,
                        ];
                    }),
                    'photos' => $item->photos->map(function ($photo) {
                        return [
                            'id' => $photo->id,
                            'file_path' => $photo->file_path,
                            'uploaded_at' => $photo->uploaded_at?->toISOString(),
                        ];
                    }),
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
