<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SiteController extends Controller
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

        $query = Site::with(['farm']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $sites = $query->orderBy('name')->paginate(20);
        return response()->json($sites);
    }

    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'farm_id' => 'nullable|exists:farms,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:sites,code',
            'type' => 'required|in:farmland,warehouse,factory,greenhouse,estate',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
            'total_area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $prefix = match($validated['type']) {
                'farmland' => 'FL',
                'warehouse' => 'WH',
                'factory' => 'FT',
                'greenhouse' => 'GH',
                'estate' => 'EST',
                default => 'ST'
            };
            $validated['code'] = $prefix . '-' . strtoupper(Str::random(8));
        }

        $validated['area_unit'] = $validated['area_unit'] ?? 'hectares';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $site = Site::create($validated);

        return response()->json(['data' => $site->load('farm')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $site = Site::with(['farm', 'farmZones', 'greenhouses', 'factories'])->findOrFail($id);
        return response()->json(['data' => $site]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $site = Site::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:sites,code,' . $id,
            'type' => 'sometimes|in:farmland,warehouse,factory,greenhouse,estate',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
            'total_area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $site->update($validated);

        return response()->json(['data' => $site->load('farm')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $site = Site::findOrFail($id);
        $site->delete();

        return response()->json(null, 204);
    }
}
