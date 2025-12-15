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
        $query = GariInventory::where('status', 'IN_STOCK');

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $summary = $query->selectRaw('
            gari_type,
            gari_grade,
            packaging_type,
            SUM(quantity_kg) as total_kg,
            SUM(quantity_units) as total_units,
            SUM(total_cost) as total_cost_value
        ')
        ->groupBy('gari_type', 'gari_grade', 'packaging_type')
        ->get();

        return response()->json(['data' => $summary]);
    }
}

