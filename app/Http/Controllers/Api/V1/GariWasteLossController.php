<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GariWasteLoss;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GariWasteLossController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GariWasteLoss::with(['farm', 'gariProductionBatch', 'gariInventory']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('loss_type')) {
            $query->where('loss_type', $request->loss_type);
        }

        if ($request->has('date_from')) {
            $query->where('loss_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('loss_date', '<=', $request->date_to);
        }

        $losses = $query->orderBy('loss_date', 'desc')->paginate(20);
        return response()->json($losses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'gari_inventory_id' => 'nullable|exists:gari_inventory,id',
            'loss_date' => 'required|date',
            'loss_type' => 'required|in:SPOILAGE,MOISTURE_DAMAGE,SPILLAGE,REJECTED_BATCH,CUSTOMER_RETURN,THEFT,OTHER',
            'gari_type' => 'nullable|in:WHITE,YELLOW',
            'packaging_type' => 'nullable|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'required|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Calculate total loss value
        if (isset($validated['cost_per_kg']) && $validated['cost_per_kg'] > 0) {
            $validated['total_loss_value'] = $validated['quantity_kg'] * $validated['cost_per_kg'];
        }

        $loss = GariWasteLoss::create($validated);

        return response()->json(['data' => $loss->load('farm', 'gariProductionBatch', 'gariInventory')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $loss = GariWasteLoss::with(['farm', 'gariProductionBatch', 'gariInventory'])->findOrFail($id);
        return response()->json(['data' => $loss]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $loss = GariWasteLoss::findOrFail($id);

        $validated = $request->validate([
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'gari_inventory_id' => 'nullable|exists:gari_inventory,id',
            'loss_date' => 'sometimes|date',
            'loss_type' => 'sometimes|in:SPOILAGE,MOISTURE_DAMAGE,SPILLAGE,REJECTED_BATCH,CUSTOMER_RETURN,THEFT,OTHER',
            'gari_type' => 'nullable|in:WHITE,YELLOW',
            'packaging_type' => 'nullable|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'sometimes|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Recalculate total loss value if needed
        if (isset($validated['cost_per_kg']) && isset($validated['quantity_kg'])) {
            $validated['total_loss_value'] = $validated['quantity_kg'] * $validated['cost_per_kg'];
        }

        $loss->update($validated);

        return response()->json(['data' => $loss->load('farm', 'gariProductionBatch', 'gariInventory')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $loss = GariWasteLoss::findOrFail($id);
        $loss->delete();

        return response()->json(null, 204);
    }
}

