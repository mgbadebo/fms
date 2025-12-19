<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Borehole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BoreholeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Borehole::with(['farm', 'location']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $boreholes = $query->orderBy('name')->paginate(20);
        return response()->json($boreholes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'installed_date' => 'required|date',
            'installation_cost' => 'required|numeric|min:0',
            'amortization_cycles' => 'nullable|integer|min:1',
            'location_id' => 'nullable|exists:locations,id',
            'specifications' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $validated['code'] = 'BH-' . strtoupper(Str::random(8));
        }

        // Set defaults
        $validated['amortization_cycles'] = $validated['amortization_cycles'] ?? 6;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $borehole = Borehole::create($validated);

        return response()->json(['data' => $borehole->load('farm')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $borehole = Borehole::with(['farm', 'greenhouses', 'location'])->findOrFail($id);
        return response()->json(['data' => $borehole]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $borehole = Borehole::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'installed_date' => 'sometimes|date',
            'installation_cost' => 'sometimes|numeric|min:0',
            'amortization_cycles' => 'nullable|integer|min:1',
            'location_id' => 'nullable|exists:locations,id',
            'specifications' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $borehole->update($validated);

        return response()->json(['data' => $borehole->load('farm', 'greenhouses', 'location')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $borehole = Borehole::findOrFail($id);
        $borehole->delete();

        return response()->json(null, 204);
    }
}
