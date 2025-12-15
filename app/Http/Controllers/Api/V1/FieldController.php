<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FieldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Field::with(['farm', 'zones', 'cropPlans']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $fields = $query->paginate(20);
        return response()->json($fields);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'geometry_reference' => 'nullable|string',
            'geometry' => 'nullable|array',
            'area' => 'nullable|numeric',
            'area_unit' => 'nullable|string|default:hectares',
            'soil_type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $field = Field::create($validated);

        return response()->json(['data' => $field->load('farm')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $field = Field::with(['farm', 'zones', 'cropPlans', 'harvestLots'])->findOrFail($id);
        return response()->json(['data' => $field]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $field = Field::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'geometry_reference' => 'nullable|string',
            'geometry' => 'nullable|array',
            'area' => 'nullable|numeric',
            'area_unit' => 'nullable|string',
            'soil_type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $field->update($validated);

        return response()->json(['data' => $field->load('farm')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $field = Field::findOrFail($id);
        $field->delete();

        return response()->json(null, 204);
    }
}

