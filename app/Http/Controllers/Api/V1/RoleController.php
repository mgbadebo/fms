<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MenuPermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
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
     * List all roles
     */
    public function index(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $roles = Role::with('permissions')->get();
        return response()->json(['data' => $roles]);
    }

    /**
     * Get a specific role with permissions
     */
    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $role = Role::with('permissions')->findOrFail($id);
        $menuPermissions = MenuPermission::getGroupedPermissions();
        
        return response()->json([
            'data' => $role,
            'menu_permissions' => $menuPermissions,
        ]);
    }

    /**
     * Create a new role
     */
    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        // If creating ADMIN role, assign all permissions automatically
        if ($validated['name'] === 'ADMIN') {
            $allPermissions = Permission::all();
            $role->syncPermissions($allPermissions);
        } elseif (isset($validated['permissions']) && is_array($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return response()->json(['data' => $role->load('permissions')], 201);
    }

    /**
     * Update a role
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }

        // If updating ADMIN role, always assign all permissions automatically
        if ($role->name === 'ADMIN') {
            $allPermissions = Permission::all();
            $role->syncPermissions($allPermissions);
        } elseif (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return response()->json(['data' => $role->load('permissions')]);
    }

    /**
     * Delete a role
     */
    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $role = Role::findOrFail($id);
        
        // Prevent deleting ADMIN role
        if ($role->name === 'ADMIN') {
            return response()->json(['message' => 'Cannot delete ADMIN role'], 422);
        }

        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Get all available menu permissions
     */
    public function menuPermissions(): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $menuPermissions = MenuPermission::getGroupedPermissions();
        $allPermissions = Permission::all()->pluck('name')->toArray();

        return response()->json([
            'data' => $menuPermissions,
            'all_permissions' => $allPermissions,
        ]);
    }
}
