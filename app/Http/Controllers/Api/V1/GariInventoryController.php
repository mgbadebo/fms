<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GariInventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GariInventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GariInventory::with(['farm', 'gariProductionBatch', 'location']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('gari_type')) {
            $query->where('gari_type', $request->gari_type);
        }

        if ($request->has('packaging_type')) {
            $query->where('packaging_type', $request->packaging_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $inventory = $query->orderBy('production_date', 'desc')->paginate(20);
        return response()->json($inventory);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'gari_type' => 'required|in:WHITE,YELLOW',
            'gari_grade' => 'required|in:FINE,COARSE,MIXED',
            'packaging_type' => 'required|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'required|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'location_id' => 'nullable|exists:inventory_locations,id',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:IN_STOCK,RESERVED,SOLD,SPOILED,DAMAGED',
            'production_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Calculate total cost
        if (isset($validated['cost_per_kg']) && $validated['cost_per_kg'] > 0) {
            $validated['total_cost'] = $validated['quantity_kg'] * $validated['cost_per_kg'];
        }

        $inventory = GariInventory::create($validated);

        return response()->json(['data' => $inventory->load('farm', 'gariProductionBatch', 'location')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $inventory = GariInventory::with(['farm', 'gariProductionBatch', 'location'])->findOrFail($id);
        return response()->json(['data' => $inventory]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $inventory = GariInventory::findOrFail($id);

        $validated = $request->validate([
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'gari_type' => 'sometimes|in:WHITE,YELLOW',
            'gari_grade' => 'sometimes|in:FINE,COARSE,MIXED',
            'packaging_type' => 'sometimes|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'sometimes|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'location_id' => 'nullable|exists:inventory_locations,id',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:IN_STOCK,RESERVED,SOLD,SPOILED,DAMAGED',
            'production_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Recalculate total cost if needed
        if (isset($validated['cost_per_kg']) && isset($validated['quantity_kg'])) {
            $validated['total_cost'] = $validated['quantity_kg'] * $validated['cost_per_kg'];
        }

        $inventory->update($validated);

        return response()->json(['data' => $inventory->load('farm', 'gariProductionBatch', 'location')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $inventory = GariInventory::findOrFail($id);
        $inventory->delete();

        return response()->json(null, 204);
    }

    // Get inventory summary by type and packaging
    public function summary(Request $request): JsonResponse
    {
        $farmId = $request->input('farm_id');
        
        // Get inventory from gari_inventory table
        $inventoryQuery = GariInventory::where('status', 'IN_STOCK');
        if ($farmId) {
            $inventoryQuery->where('farm_id', $farmId);
        }
        
        $inventorySummary = $inventoryQuery->selectRaw('
            gari_type,
            gari_grade,
            packaging_type,
            SUM(quantity_kg) as total_kg,
            SUM(quantity_units) as total_units,
            SUM(total_cost) as total_cost_value
        ')
        ->groupBy('gari_type', 'gari_grade', 'packaging_type')
        ->get();
        
        // Also get production batches that haven't been converted to inventory
        $batchesQuery = \App\Models\GariProductionBatch::where('gari_produced_kg', '>', 0);
        if ($farmId) {
            $batchesQuery->where('farm_id', $farmId);
        }
        
        $batches = $batchesQuery->get();
        
        // Calculate sold quantities per batch
        $batchIds = $batches->pluck('id');
        $soldQuantities = \App\Models\GariSale::whereIn('gari_production_batch_id', $batchIds)
            ->selectRaw('gari_production_batch_id, SUM(quantity_kg) as sold_kg')
            ->groupBy('gari_production_batch_id')
            ->pluck('sold_kg', 'gari_production_batch_id');
        
        // Get inventory quantities per batch
        $inventoryQuantities = GariInventory::whereIn('gari_production_batch_id', $batchIds)
            ->where('status', 'IN_STOCK')
            ->selectRaw('gari_production_batch_id, SUM(quantity_kg) as inventory_kg')
            ->groupBy('gari_production_batch_id')
            ->pluck('inventory_kg', 'gari_production_batch_id');
        
        // Add batch data to summary
        $summaryMap = [];
        
        // Add inventory items to map
        foreach ($inventorySummary as $item) {
            $key = $item->gari_type . '_' . $item->gari_grade . '_' . $item->packaging_type;
            $summaryMap[$key] = [
                'gari_type' => $item->gari_type,
                'gari_grade' => $item->gari_grade,
                'packaging_type' => $item->packaging_type,
                'total_kg' => (float)$item->total_kg,
                'total_units' => (int)$item->total_units,
                'total_cost_value' => (float)$item->total_cost_value,
            ];
        }
        
        // Add batches that haven't been fully converted to inventory
        foreach ($batches as $batch) {
            $soldKg = (float)($soldQuantities[$batch->id] ?? 0);
            $inventoryKg = (float)($inventoryQuantities[$batch->id] ?? 0);
            $availableKg = $batch->gari_produced_kg - $soldKg - $inventoryKg;
            
            if ($availableKg > 0) {
                $key = $batch->gari_type . '_' . $batch->gari_grade . '_BULK';
                
                if (!isset($summaryMap[$key])) {
                    $summaryMap[$key] = [
                        'gari_type' => $batch->gari_type,
                        'gari_grade' => $batch->gari_grade,
                        'packaging_type' => 'BULK',
                        'total_kg' => 0,
                        'total_units' => 0,
                        'total_cost_value' => 0,
                    ];
                }
                
                $summaryMap[$key]['total_kg'] += $availableKg;
                $summaryMap[$key]['total_cost_value'] += $availableKg * ($batch->cost_per_kg_gari ?? 0);
            }
        }
        
        return response()->json([
            'data' => array_values($summaryMap),
            'totalStock' => array_sum(array_column($summaryMap, 'total_kg'))
        ]);
    }
}

