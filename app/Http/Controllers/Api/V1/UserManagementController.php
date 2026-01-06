<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\AttachUserToFarmRequest;
use App\Http\Requests\UpdateFarmMembershipRequest;
use App\Http\Requests\AssignJobRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Farm;
use App\Models\UserJobRoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
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
     * List all users
     */
    public function index(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $users = User::with(['farms', 'permissions'])->paginate(20);
        
        // Use UserResource collection which handles pagination automatically
        return UserResource::collection($users)->response();
    }

    /**
     * Get a specific user
     */
    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $user = User::with([
            'farms',
            'jobRoleAssignments.workerJobRole',
            'permissions'
        ])->findOrFail($id);

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Create a new user (with optional photo, farm memberships, permissions, job roles)
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null,
                'password' => Hash::make($request->password),
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $path = $photo->store("users/{$user->id}", 'public');
                $user->profile_photo_path = $path;
                $user->save();
            }

            // Attach to farms (if provided) - handle JSON string from FormData
            $farmsData = $request->input('farms');
            if ($farmsData) {
                if (is_string($farmsData)) {
                    $farmsData = json_decode($farmsData, true);
                }
                if (is_array($farmsData)) {
                    foreach ($farmsData as $farmData) {
                        $user->farms()->attach($farmData['farm_id'], [
                            'membership_status' => $farmData['membership_status'] ?? 'ACTIVE',
                            'employment_category' => $farmData['employment_category'] ?? null,
                            'pay_type' => $farmData['pay_type'] ?? null,
                            'pay_rate' => $farmData['pay_rate'] ?? null,
                            'start_date' => $farmData['start_date'] ?? null,
                            'end_date' => $farmData['end_date'] ?? null,
                            'notes' => $farmData['notes'] ?? null,
                        ]);
                    }
                }
            }

            // Grant permissions (if provided) - handle JSON string from FormData
            $permissionsData = $request->input('permissions');
            if ($permissionsData) {
                if (is_string($permissionsData)) {
                    $permissionsData = json_decode($permissionsData, true);
                }
                if (is_array($permissionsData)) {
                    $permissions = Permission::whereIn('name', $permissionsData)->get();
                    $user->givePermissionTo($permissions);
                }
            }

            // Assign job roles (if provided) - handle JSON string from FormData
            $jobRolesData = $request->input('job_roles');
            if ($jobRolesData) {
                if (is_string($jobRolesData)) {
                    $jobRolesData = json_decode($jobRolesData, true);
                }
                if (is_array($jobRolesData)) {
                    foreach ($jobRolesData as $jobRoleData) {
                        // Job roles are now global, no need to verify farm_id
                        $jobRole = \App\Models\WorkerJobRole::findOrFail($jobRoleData['worker_job_role_id']);

                        UserJobRoleAssignment::create([
                            'farm_id' => $jobRoleData['farm_id'],
                            'user_id' => $user->id,
                            'worker_job_role_id' => $jobRoleData['worker_job_role_id'],
                            'assigned_at' => $jobRoleData['assigned_at'] ?? now(),
                            'assigned_by_user_id' => $request->user()->id,
                            'notes' => $jobRoleData['notes'] ?? null,
                        ]);
                    }
                }
            }

            $user->load(['farms', 'jobRoleAssignments.workerJobRole', 'permissions']);
            return response()->json(['data' => new UserResource($user)], 201);
        });
    }

    /**
     * Update a user
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        return DB::transaction(function () use ($request, $id) {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'password' => 'sometimes|string|min:8',
            ]);

            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }

            if (isset($validated['phone'])) {
                $user->phone = $validated['phone'];
            }

            if (isset($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            // Handle farm memberships update (if provided)
            $farmsData = $request->input('farms');
            if ($farmsData !== null) {
                if (is_string($farmsData)) {
                    $farmsData = json_decode($farmsData, true);
                }
                if (is_array($farmsData)) {
                    // Sync farms - detach all first, then attach new ones
                    $user->farms()->detach();
                    foreach ($farmsData as $farmData) {
                        if (!empty($farmData['farm_id'])) {
                            $user->farms()->attach($farmData['farm_id'], [
                                'membership_status' => $farmData['membership_status'] ?? 'ACTIVE',
                                'employment_category' => $farmData['employment_category'] ?? null,
                                'pay_type' => $farmData['pay_type'] ?? null,
                                'pay_rate' => $farmData['pay_rate'] ?? null,
                                'start_date' => $farmData['start_date'] ?? null,
                                'end_date' => $farmData['end_date'] ?? null,
                                'notes' => $farmData['notes'] ?? null,
                            ]);
                        }
                    }
                }
            }

            // Handle permissions update (if provided)
            $permissionsData = $request->input('permissions');
            if ($permissionsData !== null) {
                if (is_string($permissionsData)) {
                    $permissionsData = json_decode($permissionsData, true);
                }
                if (is_array($permissionsData)) {
                    $permissions = Permission::whereIn('name', $permissionsData)->get();
                    $user->syncPermissions($permissions);
                }
            }

            $user->load(['farms', 'jobRoleAssignments.workerJobRole', 'permissions']);
            return response()->json(['data' => new UserResource($user)]);
        });
    }

    /**
     * Delete a user
     */
    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }

        // Delete profile photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * Attach user to a farm
     */
    public function attachToFarm(AttachUserToFarmRequest $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $farm = Farm::findOrFail($request->farm_id);

        // Check if already attached
        if ($user->farms->contains($farm->id)) {
            return response()->json(['message' => 'User is already attached to this farm'], 422);
        }

        $user->farms()->attach($request->farm_id, [
            'membership_status' => $request->membership_status,
            'employment_category' => $request->employment_category,
            'pay_type' => $request->pay_type,
            'pay_rate' => $request->pay_rate,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
        ]);

        $user->load('farms');
        return response()->json(['data' => new UserResource($user)], 201);
    }

    /**
     * Update farm membership
     */
    public function updateFarmMembership(UpdateFarmMembershipRequest $request, string $userId, string $farmId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        if (!$user->farms->contains($farmId)) {
            return response()->json(['message' => 'User is not attached to this farm'], 404);
        }

        $user->farms()->updateExistingPivot($farmId, $request->validated());
        $user->load('farms');

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Detach user from farm (set membership_status to INACTIVE)
     */
    public function detachFromFarm(string $userId, string $farmId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        if (!$user->farms->contains($farmId)) {
            return response()->json(['message' => 'User is not attached to this farm'], 404);
        }

        $user->farms()->updateExistingPivot($farmId, [
            'membership_status' => 'INACTIVE',
        ]);

        $user->load('farms');
        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Assign job role to user for a specific farm
     */
    public function assignJobRole(AssignJobRoleRequest $request, string $userId, string $farmId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $jobRole = \App\Models\WorkerJobRole::findOrFail($request->worker_job_role_id);

        // Job roles are global, no need to verify farm_id

        // Check for duplicate active assignment
        $existing = UserJobRoleAssignment::where('farm_id', $farmId)
            ->where('user_id', $userId)
            ->where('worker_job_role_id', $request->worker_job_role_id)
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'User already has this active job role assignment'], 422);
        }

        $assignment = UserJobRoleAssignment::create([
            'farm_id' => $farmId,
            'user_id' => $userId,
            'worker_job_role_id' => $request->worker_job_role_id,
            'assigned_at' => $request->assigned_at ?? now(),
            'assigned_by_user_id' => $request->user()->id,
            'notes' => $request->notes,
        ]);

        $user->load(['jobRoleAssignments.workerJobRole']);
        return response()->json(['data' => new UserResource($user)], 201);
    }

    /**
     * End job role assignment
     */
    public function endJobRole(string $userId, string $farmId, string $assignmentId): JsonResponse
    {
        $assignment = UserJobRoleAssignment::where('id', $assignmentId)
            ->where('user_id', $userId)
            ->where('farm_id', $farmId)
            ->firstOrFail();

        $assignment->ended_at = now();
        $assignment->save();

        $user = User::with(['jobRoleAssignments.workerJobRole'])->findOrFail($userId);
        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Get job roles for user in a specific farm
     */
    public function getJobRoles(string $userId, string $farmId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        $assignments = UserJobRoleAssignment::where('user_id', $userId)
            ->where('farm_id', $farmId)
            ->with('workerJobRole')
            ->get();

        return response()->json(['data' => $assignments]);
    }

    /**
     * Upload user photo
     */
    public function uploadPhoto(Request $request, string $userId): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        $user = User::findOrFail($userId);

        // Delete old photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Store new photo
        $photo = $request->file('photo');
        $path = $photo->store("users/{$user->id}", 'public');
        $user->profile_photo_path = $path;
        $user->save();

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Delete user photo
     */
    public function deletePhoto(string $userId): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $user = User::findOrFail($userId);

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        }

        return response()->json(['data' => new UserResource($user)]);
    }
}
