<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FarmController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farms = Farm::with(['users', 'seasons'])->paginate(20);
        return response()->json($farms);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'total_area' => 'nullable|numeric',
            'area_unit' => 'nullable|string|default:hectares',
            'is_active' => 'boolean',
        ]);

        $farm = Farm::create($validated);

        return response()->json(['data' => $farm], 201);
    }

    public function show(string $id): JsonResponse
    {
        $farm = Farm::with(['users', 'seasons', 'fields', 'cropPlans'])->findOrFail($id);
        return response()->json(['data' => $farm]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $farm = Farm::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'total_area' => 'nullable|numeric',
            'area_unit' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $farm->update($validated);

        return response()->json(['data' => $farm]);
    }

    public function destroy(string $id): JsonResponse
    {
        $farm = Farm::findOrFail($id);
        $farm->delete();

        return response()->json(null, 204);
    }
}
