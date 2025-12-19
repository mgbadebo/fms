<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Location::with(['zones']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $locations = $query->orderBy('name')->paginate(20);
        return response()->json($locations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:locations,code',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $validated['code'] = 'LOC-' . strtoupper(Str::random(8));
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        $location = Location::create($validated);

        return response()->json(['data' => $location->load('zones')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $location = Location::with(['zones', 'farms', 'greenhouses', 'boreholes'])->findOrFail($id);
        return response()->json(['data' => $location]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:locations,code,' . $id,
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        return response()->json(['data' => $location->load('zones')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json(null, 204);
    }
}
