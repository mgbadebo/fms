<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FarmZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FarmZoneController extends Controller
{
    /**
     * Check if user has admin permission
     */
    private function checkAdminPermission(): ?JsonResponse
    {
        $user = request()->user();
        if (!$user || !$user->hasRole('ADMIN')) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }
        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $query = FarmZone::with(['site', 'crop']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('produce_type')) {
            $query->where('produce_type', $request->produce_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $zones = $query->orderBy('name')->paginate(20);
        return response()->json($zones);
    }

    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'crop_id' => 'nullable|exists:crops,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string',
            'description' => 'nullable|string',
            'area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string',
            'produce_type' => 'nullable|string|max:255',
            'geometry' => 'nullable|array',
            'soil_type' => 'nullable|string',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['area_unit'] = $validated['area_unit'] ?? 'hectares';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $zone = FarmZone::create($validated);

        return response()->json(['data' => $zone->load('site', 'crop')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $zone = FarmZone::with(['site', 'crop', 'staffAssignments.worker'])->findOrFail($id);
        return response()->json(['data' => $zone]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $zone = FarmZone::findOrFail($id);

        $validated = $request->validate([
            'crop_id' => 'nullable|exists:crops,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string',
            'description' => 'nullable|string',
            'area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string',
            'produce_type' => 'nullable|string|max:255',
            'geometry' => 'nullable|array',
            'soil_type' => 'nullable|string',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $zone->update($validated);

        return response()->json(['data' => $zone->load('site', 'crop')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $zone = FarmZone::findOrFail($id);
        $zone->delete();

        return response()->json(null, 204);
    }
}
