<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer;
use App\Models\GreenhouseProductionCycle;
use App\Models\BellPepperHarvest;
use App\Models\ProductionCycleHarvestRecord;
use App\Models\HarvestLot;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('sales_orders.create')) {
            return false;
        }
        
        // Verify user belongs to the farm
        $farmId = $this->input('farm_id');
        if ($farmId) {
            return $user->farms()->where('farms.id', $farmId)->exists();
        }
        
        return false;
    }

    public function rules(): array
    {
        return [
            'farm_id' => 'required|exists:farms,id',
            'site_id' => 'nullable|exists:sites,id',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'status' => 'sometimes|in:DRAFT,CONFIRMED,DISPATCHED,INVOICED,PAID,COMPLETED,CANCELLED',
            'currency' => 'sometimes|string|size:3',
            'due_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.production_cycle_id' => 'nullable|exists:greenhouse_production_cycles,id',
            'items.*.harvest_record_id' => 'nullable|exists:bell_pepper_harvests,id',
            'items.*.harvest_lot_id' => 'nullable|exists:harvest_lots,id',
            'items.*.product_name' => 'nullable|string|max:255|required_without:items.*.product_id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.quality_grade' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',
            'subtotal' => 'prohibited', // Computed server-side
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'total_amount' => 'prohibited', // Computed server-side
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Customers are now global, no need to check farm membership

            // Validate items linkage requirement
            foreach ($this->input('items', []) as $index => $item) {
                $hasLinkage = !empty($item['production_cycle_id']) || 
                             !empty($item['harvest_record_id']) || 
                             !empty($item['harvest_lot_id']);
                
                if (!$hasLinkage) {
                    $validator->errors()->add(
                        "items.{$index}.production_cycle_id",
                        "At least one of production_cycle_id, harvest_record_id, or harvest_lot_id must be provided."
                    );
                }

                // Validate referenced records belong to same farm
                if ($farmId) {
                    if (!empty($item['production_cycle_id'])) {
                        $cycle = GreenhouseProductionCycle::find($item['production_cycle_id']);
                        if ($cycle && $cycle->farm_id !== (int)$farmId) {
                            $validator->errors()->add(
                                "items.{$index}.production_cycle_id",
                                "Production cycle must belong to the same farm."
                            );
                        }
                    }
                    if (!empty($item['harvest_record_id'])) {
                        // Check both new and legacy harvest record tables
                        $harvest = ProductionCycleHarvestRecord::find($item['harvest_record_id']);
                        if (!$harvest) {
                            $harvest = BellPepperHarvest::find($item['harvest_record_id']);
                        }
                        if ($harvest && $harvest->farm_id !== (int)$farmId) {
                            $validator->errors()->add(
                                "items.{$index}.harvest_record_id",
                                "Harvest record must belong to the same farm."
                            );
                        }
                    }
                    if (!empty($item['harvest_lot_id'])) {
                        $lot = HarvestLot::find($item['harvest_lot_id']);
                        if ($lot && $lot->farm_id !== (int)$farmId) {
                            $validator->errors()->add(
                                "items.{$index}.harvest_lot_id",
                                "Harvest lot must belong to the same farm."
                            );
                        }
                    }
                }
            }
        });
    }
}
