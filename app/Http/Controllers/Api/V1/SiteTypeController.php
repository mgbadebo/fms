<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SiteTypeController extends Controller
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

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SiteType::with('sites');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('per_page')) {
            $siteTypes = $query->orderBy('name')->paginate($request->input('per_page', 20));
        } else {
            $siteTypes = $query->orderBy('name')->get();
        }

        return response()->json($siteTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:site_types,code',
            'name' => 'required|string|max:255',
            'code_prefix' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $siteType = SiteType::create($validated);
        return response()->json(['data' => $siteType], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $siteType = SiteType::with('sites')->findOrFail($id);
        return response()->json(['data' => $siteType]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $siteType = SiteType::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:255|unique:site_types,code,' . $id,
            'name' => 'sometimes|string|max:255',
            'code_prefix' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $siteType->update($validated);
        return response()->json(['data' => $siteType]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $siteType = SiteType::findOrFail($id);
        
        // Check if site type has sites
        if ($siteType->sites()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete site type with existing sites'
            ], 422);
        }

        $siteType->delete();
        return response()->json(null, 204);
    }
}
