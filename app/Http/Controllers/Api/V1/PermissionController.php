<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GrantPermissionsRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
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
     * List all available permissions
     */
    public function index(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $permissions = Permission::orderBy('name')->get();
        return response()->json(['data' => $permissions]);
    }

    /**
     * Get permissions for a specific user
     */
    public function getUserPermissions(string $userId): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $user = User::findOrFail($userId);
        $permissions = $user->permissions;

        return response()->json(['data' => $permissions]);
    }

    /**
     * Grant permissions to a user
     */
    public function grantPermissions(GrantPermissionsRequest $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $permissions = Permission::whereIn('name', $request->permissions)->get();
        
        $user->givePermissionTo($permissions);
        $user->load('permissions');

        return response()->json([
            'message' => 'Permissions granted successfully',
            'data' => $user->permissions->pluck('name')
        ]);
    }

    /**
     * Revoke permissions from a user
     */
    public function revokePermissions(Request $request, string $userId): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|exists:permissions,name',
        ]);

        $user = User::findOrFail($userId);
        $permissions = Permission::whereIn('name', $validated['permissions'])->get();
        
        $user->revokePermissionTo($permissions);
        $user->load('permissions');

        return response()->json([
            'message' => 'Permissions revoked successfully',
            'data' => $user->permissions->pluck('name')
        ]);
    }
}
