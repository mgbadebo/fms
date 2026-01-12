<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'total_amount' => $this->total_amount,
            'payment_status' => $this->payment_status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'farm' => [
                'id' => $this->farm?->id,
                'name' => $this->farm?->name,
            ],
            'site' => $this->site ? [
                'id' => $this->site->id,
                'name' => $this->site->name,
            ] : null,
            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'customer_type' => $this->customer?->customer_type,
            ],
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                    ] : null,
                    'product_name' => $item->product_name ?? $item->product_description,
                    'production_cycle' => $item->productionCycle ? [
                        'id' => $item->productionCycle->id,
                        'production_cycle_code' => $item->productionCycle->production_cycle_code,
                    ] : null,
                    'harvest_record' => (function() use ($item) {
                        if ($item->harvest_record_id) {
                            // Try new production cycle harvest record
                            $newRecord = \App\Models\ProductionCycleHarvestRecord::find($item->harvest_record_id);
                            if ($newRecord) {
                                return [
                                    'id' => $newRecord->id,
                                    'harvest_date' => $newRecord->harvest_date?->format('Y-m-d'),
                                ];
                            }
                            // Fallback to legacy
                            $legacyRecord = \App\Models\BellPepperHarvest::find($item->harvest_record_id);
                            if ($legacyRecord) {
                                return [
                                    'id' => $legacyRecord->id,
                                    'harvest_code' => $legacyRecord->harvest_code,
                                ];
                            }
                        }
                        return null;
                    })(),
                    'harvest_lot' => $item->harvestLot ? [
                        'id' => $item->harvestLot->id,
                        'code' => $item->harvestLot->code,
                    ] : null,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'line_total' => $item->line_total,
                    'quality_grade' => $item->quality_grade,
                    'notes' => $item->notes,
                ];
            }),
            'payments' => $this->whenLoaded('payments', function () {
                return $this->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_date' => $payment->payment_date?->format('Y-m-d'),
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'method' => $payment->method,
                        'reference' => $payment->reference,
                        'received_by' => $payment->receivedBy ? [
                            'id' => $payment->receivedBy->id,
                            'name' => $payment->receivedBy->name,
                        ] : null,
                    ];
                });
            }, []),
            'notes' => $this->notes,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
