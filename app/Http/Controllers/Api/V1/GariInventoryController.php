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

        $inventory = $query->orderBy('production_date', 'desc')->get();
        
        // Also include completed batches that don't have inventory records
        $batchQuery = \App\Models\GariProductionBatch::where('status', 'COMPLETED')
            ->where('gari_produced_kg', '>', 0)
            ->with('farm');
            
        if ($request->has('farm_id')) {
            $batchQuery->where('farm_id', $request->farm_id);
        }
        
        $batches = $batchQuery->get();
        
        // Get batch IDs that already have inventory
        $batchesWithInventory = $inventory->pluck('gari_production_batch_id')->filter()->unique();
        
        // Get sold quantities for batches
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
        
        // Create inventory items for batches without inventory records
        foreach ($batches as $batch) {
            if ($batchesWithInventory->contains($batch->id)) {
                continue; // Skip batches that already have inventory records
            }
            
            $soldKg = (float)($soldQuantities[$batch->id] ?? 0);
            $inventoryKg = (float)($inventoryQuantities[$batch->id] ?? 0);
            $availableKg = $batch->gari_produced_kg - $soldKg - $inventoryKg;
            
            if ($availableKg > 0) {
                // Create a virtual inventory item from the batch
                $virtualInventory = new GariInventory([
                    'id' => null, // Virtual item, no database ID
                    'farm_id' => $batch->farm_id,
                    'gari_production_batch_id' => $batch->id,
                    'gari_type' => $batch->gari_type,
                    'gari_grade' => $batch->gari_grade,
                    'packaging_type' => 'BULK',
                    'quantity_kg' => $availableKg,
                    'quantity_units' => 0,
                    'cost_per_kg' => $batch->cost_per_kg_gari,
                    'total_cost' => $availableKg * ($batch->cost_per_kg_gari ?? 0),
                    'status' => 'IN_STOCK',
                    'production_date' => $batch->processing_date,
                    'expiry_date' => null,
                    'notes' => 'Auto-generated from production batch',
                ]);
                
                // Set relationships
                $virtualInventory->setRelation('farm', $batch->farm);
                $virtualInventory->setRelation('gariProductionBatch', $batch);
                $virtualInventory->setRelation('location', null);
                
                $inventory->push($virtualInventory);
            }
        }
        
        // Apply filters to combined results
        if ($request->has('gari_type')) {
            $inventory = $inventory->filter(function($item) use ($request) {
                return $item->gari_type === $request->gari_type;
            });
        }
        
        if ($request->has('packaging_type')) {
            $inventory = $inventory->filter(function($item) use ($request) {
                return $item->packaging_type === $request->packaging_type;
            });
        }
        
        if ($request->has('status')) {
            $inventory = $inventory->filter(function($item) use ($request) {
                return $item->status === $request->status;
            });
        }
        
        // Sort by production date
        $inventory = $inventory->sortByDesc(function($item) {
            if ($item->production_date) {
                return is_string($item->production_date) 
                    ? strtotime($item->production_date) 
                    : $item->production_date->timestamp;
            }
            if ($item->created_at) {
                return is_string($item->created_at) 
                    ? strtotime($item->created_at) 
                    : $item->created_at->timestamp;
            }
            return 0;
        })->values();
        
        // Convert collection to array for JSON serialization
        $inventoryArray = $inventory->map(function($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'gari_production_batch_id' => $item->gari_production_batch_id,
                'gari_type' => $item->gari_type,
                'gari_grade' => $item->gari_grade,
                'packaging_type' => $item->packaging_type,
                'quantity_kg' => (float)$item->quantity_kg,
                'quantity_units' => (int)($item->quantity_units ?? 0),
                'cost_per_kg' => $item->cost_per_kg ? (float)$item->cost_per_kg : null,
                'total_cost' => $item->total_cost ? (float)$item->total_cost : 0,
                'status' => $item->status,
                'production_date' => $item->production_date ? $item->production_date->format('Y-m-d') : null,
                'expiry_date' => $item->expiry_date ? $item->expiry_date->format('Y-m-d') : null,
                'notes' => $item->notes,
                'farm' => $item->farm ? [
                    'id' => $item->farm->id,
                    'name' => $item->farm->name,
                ] : null,
                'gari_production_batch' => $item->gariProductionBatch ? [
                    'id' => $item->gariProductionBatch->id,
                    'batch_code' => $item->gariProductionBatch->batch_code,
                ] : null,
            ];
        })->toArray();
        
        // Paginate manually
        $page = $request->input('page', 1);
        $perPage = 20;
        $total = count($inventoryArray);
        $items = array_slice($inventoryArray, ($page - 1) * $perPage, $perPage);
        
        return response()->json([
            'data' => $items,
            'current_page' => (int)$page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ]);
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
        
        // Also get completed production batches
        $batchesQuery = \App\Models\GariProductionBatch::where('status', 'COMPLETED')
            ->where('gari_produced_kg', '>', 0);
        if ($farmId) {
            $batchesQuery->where('farm_id', $farmId);
        }
        
        $batches = $batchesQuery->get();
        
        // Calculate sold and inventory quantities per batch
        $batchIds = $batches->pluck('id');
        $soldQuantities = \App\Models\GariSale::whereIn('gari_production_batch_id', $batchIds)
            ->selectRaw('gari_production_batch_id, SUM(quantity_kg) as sold_kg')
            ->groupBy('gari_production_batch_id')
            ->pluck('sold_kg', 'gari_production_batch_id');
        
        $inventoryQuantities = GariInventory::whereIn('gari_production_batch_id', $batchIds)
            ->where('status', 'IN_STOCK')
            ->selectRaw('gari_production_batch_id, SUM(quantity_kg) as inventory_kg')
            ->groupBy('gari_production_batch_id')
            ->pluck('inventory_kg', 'gari_production_batch_id');
        
        // Create summary map
        $summaryMap = [];
        
        // Add inventory items
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
        
        // Add batches without inventory records
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
        
        $totalStock = array_sum(array_column($summaryMap, 'total_kg'));
        
        return response()->json([
            'data' => array_values($summaryMap),
            'totalStock' => $totalStock
        ]);
    }
}

