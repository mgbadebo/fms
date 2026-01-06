<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AdminZoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AdminZone::with(['site']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $zones = $query->orderBy('name')->paginate(20);
        return response()->json($zones);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:admin_zones,code',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $validated['code'] = 'ZONE-' . strtoupper(Str::random(8));
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        $zone = AdminZone::create($validated);

        return response()->json(['data' => $zone->load('site')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $zone = AdminZone::with(['site', 'farms'])->findOrFail($id);
        return response()->json(['data' => $zone]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $zone = AdminZone::findOrFail($id);

        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:admin_zones,code,' . $id,
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $zone->update($validated);

        return response()->json(['data' => $zone->load('site')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $zone = AdminZone::findOrFail($id);
        $zone->delete();

        return response()->json(null, 204);
    }
}
