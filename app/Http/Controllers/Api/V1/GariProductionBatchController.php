<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GariProductionBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class GariProductionBatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GariProductionBatch::with(['farm', 'cassavaInputs']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('processing_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('processing_date', '<=', $request->date_to);
        }

        $batches = $query->orderBy('processing_date', 'desc')->paginate(20);
        return response()->json($batches);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'processing_date' => 'required|date',
            'cassava_source' => 'required|in:HARVESTED,PURCHASED,MIXED',
            'cassava_quantity_kg' => 'required|numeric|min:0',
            'cassava_cost_per_kg' => 'nullable|numeric|min:0',
            'gari_produced_kg' => 'required|numeric|min:0',
            'gari_type' => 'required|in:WHITE,YELLOW',
            'gari_grade' => 'required|in:FINE,COARSE,MIXED',
            'labour_cost' => 'nullable|numeric|min:0',
            'fuel_cost' => 'nullable|numeric|min:0',
            'equipment_cost' => 'nullable|numeric|min:0',
            'water_cost' => 'nullable|numeric|min:0',
            'transport_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'waste_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Generate batch code
        $validated['batch_code'] = 'GARI-' . strtoupper(Str::random(8));

        // Calculate costs
        if (isset($validated['cassava_cost_per_kg']) && $validated['cassava_cost_per_kg'] > 0) {
            $validated['total_cassava_cost'] = $validated['cassava_quantity_kg'] * $validated['cassava_cost_per_kg'];
        }

        $batch = GariProductionBatch::create($validated);
        
        // Calculate derived fields
        $batch->calculateYield();
        $batch->calculateCosts();
        $batch->calculateWaste();
        $batch->save();

        return response()->json(['data' => $batch->load('farm', 'cassavaInputs')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $batch = GariProductionBatch::with([
            'farm',
            'cassavaInputs.harvestLot',
            'cassavaInputs.field',
            'gariInventory',
            'wasteLosses'
        ])->findOrFail($id);
        return response()->json(['data' => $batch]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $batch = GariProductionBatch::findOrFail($id);

        $validated = $request->validate([
            'processing_date' => 'sometimes|date',
            'cassava_source' => 'sometimes|in:HARVESTED,PURCHASED,MIXED',
            'cassava_quantity_kg' => 'sometimes|numeric|min:0',
            'cassava_cost_per_kg' => 'nullable|numeric|min:0',
            'gari_produced_kg' => 'sometimes|numeric|min:0',
            'gari_type' => 'sometimes|in:WHITE,YELLOW',
            'gari_grade' => 'sometimes|in:FINE,COARSE,MIXED',
            'labour_cost' => 'nullable|numeric|min:0',
            'fuel_cost' => 'nullable|numeric|min:0',
            'equipment_cost' => 'nullable|numeric|min:0',
            'water_cost' => 'nullable|numeric|min:0',
            'transport_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'waste_kg' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:PLANNED,IN_PROGRESS,COMPLETED,CANCELLED',
            'notes' => 'nullable|string',
        ]);

        // Recalculate cassava cost if needed
        if (isset($validated['cassava_cost_per_kg']) && isset($validated['cassava_quantity_kg'])) {
            $validated['total_cassava_cost'] = $validated['cassava_quantity_kg'] * $validated['cassava_cost_per_kg'];
        }

        $batch->update($validated);
        
        // Recalculate derived fields
        $batch->calculateYield();
        $batch->calculateCosts();
        $batch->calculateWaste();
        $batch->save();

        return response()->json(['data' => $batch->load('farm', 'cassavaInputs')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $batch = GariProductionBatch::findOrFail($id);
        $batch->delete();

        return response()->json(null, 204);
    }
}

