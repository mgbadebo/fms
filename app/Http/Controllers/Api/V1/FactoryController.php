<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class FactoryController extends Controller
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
        // Allow reading factories without admin permission (needed for production batch creation)
        $query = Factory::with(['site']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('farm_id')) {
            // Filter factories by farm through sites
            $query->whereHas('site', function ($q) use ($request) {
                $q->where('farm_id', $request->farm_id);
            });
        }

        if ($request->has('production_type')) {
            $query->where('production_type', $request->production_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $factories = $query->orderBy('name')->paginate(20);
        return response()->json($factories);
    }

    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:factories,code',
            'production_type' => 'required|in:gari,other',
            'description' => 'nullable|string',
            'area_sqm' => 'nullable|numeric|min:0',
            'established_date' => 'nullable|date',
            'equipment' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $prefix = match($validated['production_type']) {
                'gari' => 'GARI-FT',
                default => 'FT'
            };
            $validated['code'] = $prefix . '-' . strtoupper(Str::random(8));
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        $factory = Factory::create($validated);

        return response()->json(['data' => $factory->load('site')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $factory = Factory::with(['site', 'staffAssignments.worker', 'gariProductionBatches'])->findOrFail($id);
        return response()->json(['data' => $factory]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $factory = Factory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:factories,code,' . $id,
            'production_type' => 'sometimes|in:gari,other',
            'description' => 'nullable|string',
            'area_sqm' => 'nullable|numeric|min:0',
            'established_date' => 'nullable|date',
            'equipment' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $factory->update($validated);

        return response()->json(['data' => $factory->load('site')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $factory = Factory::findOrFail($id);
        $factory->delete();

        return response()->json(null, 204);
    }
}
