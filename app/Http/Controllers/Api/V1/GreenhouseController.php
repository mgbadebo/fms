<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Greenhouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class GreenhouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Greenhouse::with(['farm', 'boreholes', 'location']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $greenhouses = $query->orderBy('name')->paginate(20);
        return response()->json($greenhouses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'size_sqm' => 'required|numeric|min:0',
            'built_date' => 'required|date',
            'construction_cost' => 'required|numeric|min:0',
            'amortization_cycles' => 'nullable|integer|min:1',
            'borehole_ids' => 'nullable|array',
            'borehole_ids.*' => 'exists:boreholes,id',
            'location_id' => 'nullable|exists:locations,id',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $validated['code'] = 'GH-' . strtoupper(Str::random(8));
        }

        // Set defaults
        $validated['amortization_cycles'] = $validated['amortization_cycles'] ?? 6;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Extract borehole_ids before creating
        $boreholeIds = $validated['borehole_ids'] ?? [];
        unset($validated['borehole_ids']);

        $greenhouse = Greenhouse::create($validated);

        // Attach boreholes
        if (!empty($boreholeIds)) {
            $greenhouse->boreholes()->attach($boreholeIds);
        }

        return response()->json(['data' => $greenhouse->load('farm', 'boreholes')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $greenhouse = Greenhouse::with(['farm', 'bellPepperCycles', 'boreholes', 'location'])->findOrFail($id);
        return response()->json(['data' => $greenhouse]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $greenhouse = Greenhouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'size_sqm' => 'sometimes|numeric|min:0',
            'built_date' => 'sometimes|date',
            'construction_cost' => 'sometimes|numeric|min:0',
            'amortization_cycles' => 'nullable|integer|min:1',
            'borehole_ids' => 'nullable|array',
            'borehole_ids.*' => 'exists:boreholes,id',
            'location_id' => 'nullable|exists:locations,id',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Extract borehole_ids before updating
        $boreholeIds = $validated['borehole_ids'] ?? null;
        if (isset($validated['borehole_ids'])) {
            unset($validated['borehole_ids']);
        }

        $greenhouse->update($validated);

        // Sync boreholes if provided
        if ($boreholeIds !== null) {
            $greenhouse->boreholes()->sync($boreholeIds);
        }

        return response()->json(['data' => $greenhouse->load('farm', 'boreholes', 'location')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $greenhouse = Greenhouse::findOrFail($id);
        $greenhouse->delete();

        return response()->json(null, 204);
    }
}
