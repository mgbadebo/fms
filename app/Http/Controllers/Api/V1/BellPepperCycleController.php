<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BellPepperCycle;
use App\Models\Greenhouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BellPepperCycleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BellPepperCycle::with(['farm', 'greenhouse']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('greenhouse_id')) {
            $query->where('greenhouse_id', $request->greenhouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $cycles = $query->orderBy('start_date', 'desc')->paginate(20);
        return response()->json($cycles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'greenhouse_id' => 'required|exists:greenhouses,id',
            'start_date' => 'required|date',
            'expected_end_date' => 'required|date|after:start_date',
            'expected_yield_kg' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Generate cycle code
        $validated['cycle_code'] = 'BP-CYCLE-' . strtoupper(Str::random(8));
        $validated['status'] = 'PLANNED';

        // Get greenhouse to calculate expected yield per sqm
        $greenhouse = Greenhouse::findOrFail($validated['greenhouse_id']);
        if ($greenhouse->size_sqm > 0) {
            $validated['expected_yield_per_sqm'] = round($validated['expected_yield_kg'] / $greenhouse->size_sqm, 2);
        }

        $cycle = BellPepperCycle::create($validated);

        return response()->json(['data' => $cycle->load('farm', 'greenhouse')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $cycle = BellPepperCycle::with(['farm', 'greenhouse', 'costs.staff', 'harvests'])->findOrFail($id);
        
        // Calculate totals
        $cycle->total_costs = $cycle->getTotalCosts();
        $cycle->total_harvested = (float)$cycle->harvests()->sum('weight_kg');
        
        return response()->json(['data' => $cycle]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $cycle = BellPepperCycle::findOrFail($id);

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'expected_end_date' => 'sometimes|date',
            'actual_end_date' => 'nullable|date',
            'status' => 'sometimes|in:PLANNED,IN_PROGRESS,COMPLETED,CANCELLED',
            'expected_yield_kg' => 'sometimes|numeric|min:0',
            'actual_yield_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // If actual yield is updated, recalculate yield metrics
        if (isset($validated['actual_yield_kg'])) {
            $cycle->actual_yield_kg = $validated['actual_yield_kg'];
            $cycle->calculateActualYieldPerSqm();
            $cycle->calculateYieldVariance();
        }

        // If expected yield is updated, recalculate expected yield per sqm
        if (isset($validated['expected_yield_kg'])) {
            $greenhouse = $cycle->greenhouse;
            if ($greenhouse && $greenhouse->size_sqm > 0) {
                $validated['expected_yield_per_sqm'] = round($validated['expected_yield_kg'] / $greenhouse->size_sqm, 2);
            }
        }

        $cycle->update($validated);

        return response()->json(['data' => $cycle->load('farm', 'greenhouse')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $cycle = BellPepperCycle::findOrFail($id);
        $cycle->delete();

        return response()->json(null, 204);
    }
}
