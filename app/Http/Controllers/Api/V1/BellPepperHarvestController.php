<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BellPepperHarvest;
use App\Models\BellPepperCycle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BellPepperHarvestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BellPepperHarvest::with(['farm', 'cycle', 'greenhouse']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('bell_pepper_cycle_id')) {
            $query->where('bell_pepper_cycle_id', $request->bell_pepper_cycle_id);
        }

        if ($request->has('greenhouse_id')) {
            $query->where('greenhouse_id', $request->greenhouse_id);
        }

        $harvests = $query->orderBy('harvest_date', 'desc')->paginate(20);
        return response()->json($harvests);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'bell_pepper_cycle_id' => 'required|exists:bell_pepper_cycles,id',
            'greenhouse_id' => 'required|exists:greenhouses,id',
            'harvest_date' => 'required|date',
            'weight_kg' => 'required|numeric|min:0',
            'crates_count' => 'nullable|integer|min:0',
            'grade' => 'required|in:A,B,C,MIXED',
            'status' => 'nullable|in:HARVESTED,PACKED,IN_TRANSIT,DELIVERED,SOLD',
            'notes' => 'nullable|string',
        ]);

        // Generate harvest code
        $validated['harvest_code'] = 'BP-HARV-' . strtoupper(Str::random(8));
        $validated['status'] = $validated['status'] ?? 'HARVESTED';

        // Auto-calculate crates if not provided (9-10kg per crate, use 9.5kg average)
        if (!isset($validated['crates_count']) || $validated['crates_count'] == 0) {
            $validated['crates_count'] = (int)ceil($validated['weight_kg'] / 9.5);
        }

        $harvest = BellPepperHarvest::create($validated);

        // Update cycle actual yield
        $cycle = BellPepperCycle::findOrFail($validated['bell_pepper_cycle_id']);
        $cycle->actual_yield_kg = (float)$cycle->harvests()->sum('weight_kg');
        $cycle->calculateActualYieldPerSqm();
        $cycle->calculateYieldVariance();
        $cycle->save();

        return response()->json(['data' => $harvest->load('farm', 'cycle', 'greenhouse')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $harvest = BellPepperHarvest::with(['farm', 'cycle', 'greenhouse', 'sales'])->findOrFail($id);
        $harvest->remaining_weight = $harvest->getRemainingWeight();
        return response()->json(['data' => $harvest]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $harvest = BellPepperHarvest::findOrFail($id);

        $validated = $request->validate([
            'harvest_date' => 'sometimes|date',
            'weight_kg' => 'sometimes|numeric|min:0',
            'crates_count' => 'nullable|integer|min:0',
            'grade' => 'sometimes|in:A,B,C,MIXED',
            'status' => 'nullable|in:HARVESTED,PACKED,IN_TRANSIT,DELIVERED,SOLD',
            'notes' => 'nullable|string',
        ]);

        $harvest->update($validated);

        // Update cycle actual yield if weight changed
        if (isset($validated['weight_kg'])) {
            $cycle = $harvest->cycle;
            $cycle->actual_yield_kg = (float)$cycle->harvests()->sum('weight_kg');
            $cycle->calculateActualYieldPerSqm();
            $cycle->calculateYieldVariance();
            $cycle->save();
        }

        return response()->json(['data' => $harvest->load('farm', 'cycle', 'greenhouse')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $harvest = BellPepperHarvest::findOrFail($id);
        $cycle = $harvest->cycle;
        $harvest->delete();

        // Recalculate cycle yield
        $cycle->actual_yield_kg = (float)$cycle->harvests()->sum('weight_kg');
        $cycle->calculateActualYieldPerSqm();
        $cycle->calculateYieldVariance();
        $cycle->save();

        return response()->json(null, 204);
    }
}
