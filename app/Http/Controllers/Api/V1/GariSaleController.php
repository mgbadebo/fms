<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GariSale;
use App\Models\GariInventory;
use App\Models\GariProductionBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class GariSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GariSale::with(['farm', 'customer']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        $sales = $query->orderBy('sale_date', 'desc')->paginate(20);
        return response()->json($sales);
    }

    // Get available inventory batches for FIFO selection (by farm only)
    public function getAvailableBatches(Request $request): JsonResponse
    {
        $request->validate([
            'farm_id' => 'required|exists:farms,id',
        ]);

        // Get all production batches for the farm that are completed and have gari produced
        $productionBatches = GariProductionBatch::where('farm_id', $request->farm_id)
            ->where('status', 'COMPLETED')
            ->where('gari_produced_kg', '>', 0)
            ->orderBy('processing_date', 'asc') // FIFO: oldest first
            ->orderBy('created_at', 'asc')
            ->get();

        // Get all inventory items for the farm with available stock
        $inventory = GariInventory::where('farm_id', $request->farm_id)
            ->where('status', 'IN_STOCK')
            ->where('quantity_kg', '>', 0)
            ->with('gariProductionBatch')
            ->get();

        // Group inventory by batch ID for quick lookup
        $inventoryByBatch = [];
        foreach ($inventory as $item) {
            $batchId = $item->gari_production_batch_id;
            if (!$batchId) continue;
            
            if (!isset($inventoryByBatch[$batchId])) {
                $inventoryByBatch[$batchId] = [];
            }
            $inventoryByBatch[$batchId][] = $item;
        }

        // Build batches array from production batches
        $batches = [];
        foreach ($productionBatches as $batch) {
            $batchId = $batch->id;
            
            // Check if there's inventory for this batch
            $batchInventory = $inventoryByBatch[$batchId] ?? [];
            
            // Calculate total available kg
            $totalAvailableKg = 0;
            $packagingOptions = [];
            
            if (!empty($batchInventory)) {
                // Use inventory data
                foreach ($batchInventory as $item) {
                    $totalAvailableKg += $item->quantity_kg;
                    
                    $packagingType = $item->packaging_type;
                    if (!isset($packagingOptions[$packagingType])) {
                        $packagingOptions[$packagingType] = [
                            'packaging_type' => $packagingType,
                            'available_kg' => 0,
                            'cost_per_kg' => $item->cost_per_kg ?? $batch->cost_per_kg_gari,
                        ];
                    }
                    $packagingOptions[$packagingType]['available_kg'] += $item->quantity_kg;
                }
            } else {
                // No inventory records - use batch's gari_produced_kg as available
                // Check if any has been sold by summing sales for this batch
                $soldKg = GariSale::where('gari_production_batch_id', $batchId)
                    ->sum('quantity_kg');
                
                $totalAvailableKg = max(0, $batch->gari_produced_kg - $soldKg);
                
                // If there's available gari, add a default packaging option
                if ($totalAvailableKg > 0) {
                    $packagingOptions['BULK'] = [
                        'packaging_type' => 'BULK',
                        'available_kg' => $totalAvailableKg,
                        'cost_per_kg' => $batch->cost_per_kg_gari,
                    ];
                }
            }
            
            // Only include batches with available inventory
            if ($totalAvailableKg > 0) {
                $batches[] = [
                    'batch_id' => $batchId,
                    'batch_code' => $batch->batch_code ?? 'N/A',
                    'processing_date' => $batch->processing_date,
                    'gari_type' => $batch->gari_type,
                    'gari_grade' => $batch->gari_grade,
                    'cost_per_kg_gari' => $batch->cost_per_kg_gari,
                    'total_available_kg' => $totalAvailableKg,
                    'packaging_options' => array_values($packagingOptions),
                    'inventory_items' => $batchInventory,
                ];
            }
        }

        // Sort batches by production date (FIFO)
        usort($batches, function($a, $b) {
            $dateA = $a['processing_date'] ? strtotime($a['processing_date']) : 0;
            $dateB = $b['processing_date'] ? strtotime($b['processing_date']) : 0;
            return $dateA <=> $dateB;
        });

        return response()->json(['data' => $batches]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'gari_inventory_id' => 'nullable|exists:gari_inventory,id',
            'sale_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'customer_type' => 'required|in:RETAIL,BULK_BUYER,DISTRIBUTOR,CATERING,HOTEL,OTHER',
            'gari_type' => 'required|in:WHITE,YELLOW',
            'gari_grade' => 'required|in:FINE,COARSE,MIXED',
            'packaging_type' => 'required|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'required|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:CASH,TRANSFER,POS,CHEQUE,CREDIT',
            'amount_paid' => 'nullable|numeric|min:0',
            'sales_channel' => 'nullable|string|max:255',
            'sales_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Generate sale code
        $validated['sale_code'] = 'SALE-' . strtoupper(Str::random(8));

        // Set defaults
        $validated['discount'] = $validated['discount'] ?? 0;
        $validated['amount_paid'] = $validated['amount_paid'] ?? 0;

        // Calculate amounts
        $validated['total_amount'] = $validated['quantity_kg'] * $validated['unit_price'];
        $validated['final_amount'] = $validated['total_amount'] - $validated['discount'];

        // If batch is provided but cost_per_kg is not, get it from the batch
        if (isset($validated['gari_production_batch_id']) && !isset($validated['cost_per_kg'])) {
            $batch = GariProductionBatch::find($validated['gari_production_batch_id']);
            if ($batch && $batch->cost_per_kg_gari) {
                $validated['cost_per_kg'] = $batch->cost_per_kg_gari;
            }
        }

        // If inventory item is provided but cost_per_kg is not, get it from inventory
        if (isset($validated['gari_inventory_id']) && !isset($validated['cost_per_kg'])) {
            $inventory = GariInventory::find($validated['gari_inventory_id']);
            if ($inventory && $inventory->cost_per_kg) {
                $validated['cost_per_kg'] = $inventory->cost_per_kg;
            }
        }

        $sale = GariSale::create($validated);
        
        // Calculate margins and payment
        $sale->calculateMargins();
        $sale->calculatePayment();
        $sale->save();

        // Update inventory if batch/inventory is specified
        if (isset($validated['gari_inventory_id'])) {
            $inventory = GariInventory::find($validated['gari_inventory_id']);
            if ($inventory) {
                $remainingKg = $inventory->quantity_kg - $validated['quantity_kg'];
                if ($remainingKg <= 0) {
                    $inventory->status = 'SOLD';
                    $inventory->quantity_kg = 0;
                } else {
                    $inventory->quantity_kg = $remainingKg;
                }
                $inventory->save();
            }
        } elseif (isset($validated['gari_production_batch_id'])) {
            // If no specific inventory item, reduce from oldest matching inventory (FIFO)
            // Try to find matching packaging first, then fall back to any packaging
            $inventory = GariInventory::where('gari_production_batch_id', $validated['gari_production_batch_id'])
                ->where('gari_type', $validated['gari_type'])
                ->where('gari_grade', $validated['gari_grade'])
                ->where('packaging_type', $validated['packaging_type'])
                ->where('status', 'IN_STOCK')
                ->where('quantity_kg', '>', 0)
                ->orderBy('production_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->first();

            // If no matching packaging, try to find any inventory for this batch
            if (!$inventory) {
                $inventory = GariInventory::where('gari_production_batch_id', $validated['gari_production_batch_id'])
                    ->where('gari_type', $validated['gari_type'])
                    ->where('gari_grade', $validated['gari_grade'])
                    ->where('status', 'IN_STOCK')
                    ->where('quantity_kg', '>', 0)
                    ->orderBy('production_date', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->first();
            }

            if ($inventory) {
                $remainingKg = $inventory->quantity_kg - $validated['quantity_kg'];
                if ($remainingKg <= 0) {
                    $inventory->status = 'SOLD';
                    $inventory->quantity_kg = 0;
                } else {
                    $inventory->quantity_kg = $remainingKg;
                }
                $inventory->save();
                // Update sale with the inventory item used
                $sale->gari_inventory_id = $inventory->id;
                $sale->save();
            } else {
                // No inventory record exists - this means the batch hasn't been converted to inventory yet
                // The sale is still valid, but we can't deduct from inventory
                // In a real system, you might want to create inventory records automatically here
            }
        }

        return response()->json(['data' => $sale->load('farm', 'customer', 'gariProductionBatch', 'gariInventory')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $sale = GariSale::with(['farm', 'customer', 'gariProductionBatch', 'gariInventory'])->findOrFail($id);
        return response()->json(['data' => $sale]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $sale = GariSale::findOrFail($id);

        $validated = $request->validate([
            'sale_date' => 'sometimes|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'customer_type' => 'sometimes|in:RETAIL,BULK_BUYER,DISTRIBUTOR,CATERING,HOTEL,OTHER',
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'gari_inventory_id' => 'nullable|exists:gari_inventory,id',
            'gari_type' => 'sometimes|in:WHITE,YELLOW',
            'gari_grade' => 'sometimes|in:FINE,COARSE,MIXED',
            'packaging_type' => 'sometimes|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'sometimes|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'payment_method' => 'sometimes|in:CASH,TRANSFER,POS,CHEQUE,CREDIT',
            'amount_paid' => 'nullable|numeric|min:0',
            'sales_channel' => 'nullable|string|max:255',
            'sales_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Recalculate amounts if needed
        if (isset($validated['quantity_kg']) && isset($validated['unit_price'])) {
            $validated['total_amount'] = $validated['quantity_kg'] * $validated['unit_price'];
            $validated['final_amount'] = $validated['total_amount'] - ($validated['discount'] ?? 0);
        }

        $sale->update($validated);
        
        // Recalculate margins and payment
        $sale->calculateMargins();
        $sale->calculatePayment();
        $sale->save();

        return response()->json(['data' => $sale->load('farm', 'customer')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $sale = GariSale::findOrFail($id);
        $sale->delete();

        return response()->json(null, 204);
    }

    // Get sales summary/analytics
    public function summary(Request $request): JsonResponse
    {
        $query = GariSale::query();

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        $summary = $query->selectRaw('
            customer_type,
            packaging_type,
            SUM(quantity_kg) as total_kg_sold,
            SUM(final_amount) as total_revenue,
            SUM(total_cost) as total_cost,
            SUM(gross_margin) as total_margin,
            AVG(unit_price) as avg_price_per_kg,
            COUNT(*) as total_sales
        ')
        ->groupBy('customer_type', 'packaging_type')
        ->get();

        return response()->json(['data' => $summary]);
    }
}

