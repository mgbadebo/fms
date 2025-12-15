<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PackagingMaterial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PackagingMaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PackagingMaterial::with(['farm', 'location']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('material_type')) {
            $query->where('material_type', $request->material_type);
        }

        $materials = $query->orderBy('name')->paginate(20);
        return response()->json($materials);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'material_type' => 'required|in:POUCH,SACK,LABEL,SEALING_ROLL,CARTON,OTHER',
            'size' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'quantity_purchased' => 'nullable|numeric|min:0',
            'quantity_used' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:inventory_locations,id',
            'notes' => 'nullable|string',
        ]);

        // Set defaults
        $validated['unit'] = $validated['unit'] ?? 'pieces';
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['quantity_purchased'] = $validated['quantity_purchased'] ?? 0;
        $validated['quantity_used'] = $validated['quantity_used'] ?? 0;

        // Calculate closing balance and total cost
        $material = new PackagingMaterial($validated);
        $material->calculateClosingBalance();
        
        if (isset($validated['cost_per_unit']) && $validated['cost_per_unit'] > 0) {
            $material->total_cost = $validated['quantity_purchased'] * $validated['cost_per_unit'];
        }
        
        $material->save();

        return response()->json(['data' => $material->load('farm', 'location')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $material = PackagingMaterial::with(['farm', 'location'])->findOrFail($id);
        return response()->json(['data' => $material]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $material = PackagingMaterial::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'material_type' => 'sometimes|in:POUCH,SACK,LABEL,SEALING_ROLL,CARTON,OTHER',
            'size' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'quantity_purchased' => 'nullable|numeric|min:0',
            'quantity_used' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:inventory_locations,id',
            'notes' => 'nullable|string',
        ]);

        $material->update($validated);
        
        // Recalculate closing balance
        $material->calculateClosingBalance();
        
        // Recalculate total cost if needed
        if (isset($validated['cost_per_unit']) && isset($validated['quantity_purchased'])) {
            $material->total_cost = $validated['quantity_purchased'] * $validated['cost_per_unit'];
        }
        
        $material->save();

        return response()->json(['data' => $material->load('farm', 'location')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $material = PackagingMaterial::findOrFail($id);
        $material->delete();

        return response()->json(null, 204);
    }
}

