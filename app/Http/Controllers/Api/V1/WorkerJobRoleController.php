<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkerJobRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkerJobRoleController extends Controller
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
     * Display a listing of worker job roles (global).
     */
    public function index(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $query = WorkerJobRole::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $roles = $query->orderBy('name')->paginate(20);
        return response()->json($roles);
    }

    /**
     * Store a newly created worker job role.
     */
    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:worker_job_roles,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role = WorkerJobRole::create($validated);

        return response()->json(['data' => $role], 201);
    }

    /**
     * Display the specified worker job role.
     */
    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $role = WorkerJobRole::with(['userAssignments.user'])->findOrFail($id);
        return response()->json(['data' => $role]);
    }

    /**
     * Update the specified worker job role.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $role = WorkerJobRole::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:worker_job_roles,code,' . $id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role->update($validated);

        return response()->json(['data' => $role]);
    }

    /**
     * Remove the specified worker job role.
     */
    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $role = WorkerJobRole::findOrFail($id);
        $role->delete();

        return response()->json(null, 204);
    }
}
