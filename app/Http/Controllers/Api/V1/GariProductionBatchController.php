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
        $query = GariProductionBatch::with(['farm', 'factory', 'cassavaInputs']);

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
            'factory_id' => 'required|exists:factories,id',
            'processing_date' => 'required|date',
            'cassava_source' => 'required|in:HARVESTED,PURCHASED,MIXED',
            'cassava_quantity_tonnes' => 'required_without:cassava_quantity_kg|numeric|min:0',
            'cassava_quantity_kg' => 'required_without:cassava_quantity_tonnes|numeric|min:0',
            'cassava_cost_per_tonne' => 'nullable|numeric|min:0',
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

        // Handle unit conversion: if tonnes provided, convert to kg for internal calculations
        if (isset($validated['cassava_quantity_tonnes']) && $validated['cassava_quantity_tonnes'] > 0) {
            $validated['cassava_quantity_kg'] = $validated['cassava_quantity_tonnes'] * 1000;
        }

        // Calculate costs - prefer per tonne if provided
        if (isset($validated['cassava_cost_per_tonne']) && $validated['cassava_cost_per_tonne'] > 0) {
            $tonnes = $validated['cassava_quantity_tonnes'] ?? ($validated['cassava_quantity_kg'] / 1000);
            $validated['total_cassava_cost'] = $tonnes * $validated['cassava_cost_per_tonne'];
        } elseif (isset($validated['cassava_cost_per_kg']) && $validated['cassava_cost_per_kg'] > 0) {
            $kg = $validated['cassava_quantity_kg'] ?? 0;
            $validated['total_cassava_cost'] = $kg * $validated['cassava_cost_per_kg'];
        }

        $batch = GariProductionBatch::create($validated);
        
        // Calculate derived fields
        $batch->calculateYield();
        $batch->calculateCosts();
        $batch->calculateWaste();
        $batch->save();

        return response()->json(['data' => $batch->load('farm', 'factory', 'cassavaInputs')], 201);
    }

    public function show(string $gariProductionBatch): JsonResponse
    {
        // Find the batch by ID (route parameter comes as string)
        $batch = GariProductionBatch::withTrashed()->find($gariProductionBatch);
        
        if (!$batch) {
            return response()->json(['message' => 'Production batch not found'], 404);
        }
        
        // Load relationships - skip gariInventory for now to avoid table name issues
        $batch->load([
            'farm',
            'factory',
            'cassavaInputs.harvestLot',
            'cassavaInputs.field',
            'wasteLosses'
        ]);
        
        // Manually load gariInventory to avoid table name pluralization issue
        // The relationship might be trying to use 'gari_inventories' instead of 'gari_inventory'
        try {
            $batch->setRelation('gariInventory', $batch->gariInventory()->get());
        } catch (\Exception $e) {
            // If it fails, just set empty collection
            $batch->setRelation('gariInventory', collect([]));
        }
        return response()->json(['data' => $batch]);
    }

    public function update(Request $request, string $gariProductionBatch): JsonResponse
    {
        $batch = GariProductionBatch::find($gariProductionBatch);
        if (!$batch) {
            return response()->json(['message' => 'Production batch not found'], 404);
        }

        // Prevent editing completed batches
        if ($batch->status === 'COMPLETED') {
            return response()->json([
                'message' => 'Cannot edit a completed production batch'
            ], 422);
        }

        $validated = $request->validate([
            'factory_id' => 'sometimes|exists:factories,id',
            'processing_date' => 'sometimes|date',
            'cassava_source' => 'sometimes|in:HARVESTED,PURCHASED,MIXED',
            'cassava_quantity_tonnes' => 'nullable|numeric|min:0',
            'cassava_quantity_kg' => 'nullable|numeric|min:0',
            'cassava_cost_per_tonne' => 'nullable|numeric|min:0',
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

        // Handle unit conversion: if tonnes provided, convert to kg for internal calculations
        if (isset($validated['cassava_quantity_tonnes']) && $validated['cassava_quantity_tonnes'] > 0) {
            $validated['cassava_quantity_kg'] = $validated['cassava_quantity_tonnes'] * 1000;
        }

        // Recalculate cassava cost - prefer per tonne if provided
        if (isset($validated['cassava_cost_per_tonne']) && $validated['cassava_cost_per_tonne'] > 0) {
            $tonnes = $validated['cassava_quantity_tonnes'] ?? ($validated['cassava_quantity_kg'] ?? 0) / 1000;
            $validated['total_cassava_cost'] = $tonnes * $validated['cassava_cost_per_tonne'];
        } elseif (isset($validated['cassava_cost_per_kg']) && isset($validated['cassava_quantity_kg'])) {
            $validated['total_cassava_cost'] = $validated['cassava_quantity_kg'] * $validated['cassava_cost_per_kg'];
        }

        $batch->update($validated);
        
        // Recalculate derived fields
        $batch->calculateYield();
        $batch->calculateCosts();
        $batch->calculateWaste();
        $batch->save();

        return response()->json(['data' => $batch->load('farm', 'factory', 'cassavaInputs')]);
    }

    public function destroy(string $gariProductionBatch): JsonResponse
    {
        $batch = GariProductionBatch::find($gariProductionBatch);
        if (!$batch) {
            return response()->json(['message' => 'Production batch not found'], 404);
        }
        
        $batch->delete();

        return response()->json(null, 204);
    }
}

