<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CassavaInput;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CassavaInputController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CassavaInput::with(['farm', 'gariProductionBatch', 'harvestLot', 'field']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('gari_production_batch_id')) {
            $query->where('gari_production_batch_id', $request->gari_production_batch_id);
        }

        if ($request->has('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        $inputs = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($inputs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'source_type' => 'required|in:HARVESTED,PURCHASED',
            'harvest_lot_id' => 'required_if:source_type,HARVESTED|exists:harvest_lots,id',
            'field_id' => 'nullable|exists:fields,id',
            'supplier_name' => 'required_if:source_type,PURCHASED|nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'purchase_date' => 'required_if:source_type,PURCHASED|nullable|date',
            'quantity_kg' => 'required|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'variety' => 'nullable|string|max:255',
            'quality_grade' => 'nullable|in:A,B,C',
            'notes' => 'nullable|string',
        ]);

        // Calculate total cost
        if (isset($validated['cost_per_kg']) && $validated['cost_per_kg'] > 0) {
            $validated['total_cost'] = $validated['quantity_kg'] * $validated['cost_per_kg'];
        }

        $input = CassavaInput::create($validated);

        return response()->json(['data' => $input->load('farm', 'gariProductionBatch', 'harvestLot', 'field')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $input = CassavaInput::with(['farm', 'gariProductionBatch', 'harvestLot', 'field'])->findOrFail($id);
        return response()->json(['data' => $input]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $input = CassavaInput::findOrFail($id);

        $validated = $request->validate([
            'gari_production_batch_id' => 'nullable|exists:gari_production_batches,id',
            'source_type' => 'sometimes|in:HARVESTED,PURCHASED',
            'harvest_lot_id' => 'nullable|exists:harvest_lots,id',
            'field_id' => 'nullable|exists:fields,id',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'quantity_kg' => 'sometimes|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'variety' => 'nullable|string|max:255',
            'quality_grade' => 'nullable|in:A,B,C',
            'notes' => 'nullable|string',
        ]);

        // Recalculate total cost if needed
        if (isset($validated['cost_per_kg']) && isset($validated['quantity_kg'])) {
            $validated['total_cost'] = $validated['quantity_kg'] * $validated['cost_per_kg'];
        }

        $input->update($validated);

        return response()->json(['data' => $input->load('farm', 'gariProductionBatch', 'harvestLot', 'field')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $input = CassavaInput::findOrFail($id);
        $input->delete();

        return response()->json(null, 204);
    }
}

