<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ZoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Zone::with(['field', 'farm']);

        if ($request->has('field_id')) {
            $query->where('field_id', $request->field_id);
        }

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $zones = $query->paginate(20);
        return response()->json($zones);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'field_id' => 'required|exists:fields,id',
            'name' => 'required|string|max:255',
            'geometry_reference' => 'nullable|string',
            'geometry' => 'nullable|array',
            'area' => 'nullable|numeric',
            'area_unit' => 'nullable|string|default:hectares',
            'notes' => 'nullable|string',
        ]);

        $zone = Zone::create($validated);

        return response()->json(['data' => $zone->load('farm', 'field')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $zone = Zone::with(['farm', 'field', 'cropPlans', 'harvestLots'])->findOrFail($id);
        return response()->json(['data' => $zone]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'geometry_reference' => 'nullable|string',
            'geometry' => 'nullable|array',
            'area' => 'nullable|numeric',
            'area_unit' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $zone->update($validated);

        return response()->json(['data' => $zone->load('farm', 'field')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);
        $zone->delete();

        return response()->json(null, 204);
    }
}

