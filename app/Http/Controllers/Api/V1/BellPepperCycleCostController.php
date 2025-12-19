<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BellPepperCycleCost;
use App\Models\BellPepperCycle;
use App\Models\Greenhouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BellPepperCycleCostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BellPepperCycleCost::with(['cycle', 'farm', 'staff']);

        if ($request->has('bell_pepper_cycle_id')) {
            $query->where('bell_pepper_cycle_id', $request->bell_pepper_cycle_id);
        }

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('cost_type')) {
            $query->where('cost_type', $request->cost_type);
        }

        $costs = $query->orderBy('cost_date', 'desc')->paginate(50);
        return response()->json($costs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bell_pepper_cycle_id' => 'required|exists:bell_pepper_cycles,id',
            'farm_id' => 'required|exists:farms,id',
            'cost_type' => 'required|in:SEEDS,FERTILIZER_CHEMICALS,FUEL_WATER_PUMPING,LABOUR_DEDICATED,LABOUR_SHARED,SPRAY_GUNS,IRRIGATION_EQUIPMENT,PROTECTIVE_CLOTHING,GREENHOUSE_AMORTIZATION,BOREHOLE_AMORTIZATION,LOGISTICS,OTHER',
            'description' => 'nullable|string',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string',
            'unit_cost' => 'nullable|numeric|min:0',
            'total_cost' => 'required|numeric|min:0',
            'cost_date' => 'required|date',
            'staff_id' => 'nullable|exists:users,id',
            'hours_allocated' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Auto-calculate total_cost if quantity and unit_cost provided
        if (isset($validated['quantity']) && isset($validated['unit_cost']) && !isset($validated['total_cost'])) {
            $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];
        }

        // Handle amortization costs
        if ($validated['cost_type'] === 'GREENHOUSE_AMORTIZATION') {
            $cycle = BellPepperCycle::with('greenhouse')->findOrFail($validated['bell_pepper_cycle_id']);
            if ($cycle->greenhouse) {
                $validated['total_cost'] = $cycle->greenhouse->getAmortizedCostPerCycle();
                $validated['description'] = 'Greenhouse amortization (Cycle ' . $cycle->cycle_code . ')';
            }
        } elseif ($validated['cost_type'] === 'BOREHOLE_AMORTIZATION') {
            $cycle = BellPepperCycle::with('greenhouse.boreholes')->findOrFail($validated['bell_pepper_cycle_id']);
            if ($cycle->greenhouse) {
                $validated['total_cost'] = $cycle->greenhouse->getBoreholeAmortizedCostPerCycle();
                $boreholeNames = $cycle->greenhouse->boreholes->pluck('name')->join(', ');
                $validated['description'] = 'Borehole amortization' . ($boreholeNames ? ' (' . $boreholeNames . ')' : '') . ' - Cycle ' . $cycle->cycle_code;
            }
        }

        $cost = BellPepperCycleCost::create($validated);

        return response()->json(['data' => $cost->load('cycle', 'farm', 'staff')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $cost = BellPepperCycleCost::with(['cycle', 'farm', 'staff'])->findOrFail($id);
        return response()->json(['data' => $cost]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $cost = BellPepperCycleCost::findOrFail($id);

        $validated = $request->validate([
            'cost_type' => 'sometimes|in:SEEDS,FERTILIZER_CHEMICALS,FUEL_WATER_PUMPING,LABOUR_DEDICATED,LABOUR_SHARED,SPRAY_GUNS,IRRIGATION_EQUIPMENT,PROTECTIVE_CLOTHING,GREENHOUSE_AMORTIZATION,BOREHOLE_AMORTIZATION,LOGISTICS,OTHER',
            'description' => 'nullable|string',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string',
            'unit_cost' => 'nullable|numeric|min:0',
            'total_cost' => 'sometimes|numeric|min:0',
            'cost_date' => 'sometimes|date',
            'staff_id' => 'nullable|exists:users,id',
            'hours_allocated' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Recalculate total_cost if quantity or unit_cost changed
        if (isset($validated['quantity']) || isset($validated['unit_cost'])) {
            $quantity = $validated['quantity'] ?? $cost->quantity ?? 0;
            $unitCost = $validated['unit_cost'] ?? $cost->unit_cost ?? 0;
            if ($quantity > 0 && $unitCost > 0) {
                $validated['total_cost'] = $quantity * $unitCost;
            }
        }

        $cost->update($validated);

        return response()->json(['data' => $cost->load('cycle', 'farm', 'staff')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $cost = BellPepperCycleCost::findOrFail($id);
        $cost->delete();

        return response()->json(null, 204);
    }
}
