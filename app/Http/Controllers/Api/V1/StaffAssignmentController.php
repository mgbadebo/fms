<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StaffAssignment;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffAssignmentController extends Controller
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

        $query = StaffAssignment::with(['worker', 'assignable', 'assigner']);

        if ($request->has('worker_id')) {
            $query->where('worker_id', $request->worker_id);
        }

        if ($request->has('is_current')) {
            $query->where('is_current', $request->is_current);
        }

        if ($request->has('assignable_type')) {
            $query->where('assignable_type', $request->assignable_type);
        }

        if ($request->has('assignable_id')) {
            $query->where('assignable_id', $request->assignable_id);
        }

        $assignments = $query->orderBy('assigned_from', 'desc')->paginate(20);
        return response()->json($assignments);
    }

    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'assignable_type' => 'required|string|in:App\\Models\\Site,App\\Models\\Factory,App\\Models\\Greenhouse,App\\Models\\FarmZone',
            'assignable_id' => 'required|integer',
            'role' => 'nullable|string|max:255',
            'core_responsibilities' => 'nullable|string',
            'assigned_from' => 'required|date',
            'assigned_to' => 'nullable|date|after:assigned_from',
            'notes' => 'nullable|string',
        ]);

        // End any current assignments to the same assignable for this worker
        StaffAssignment::where('worker_id', $validated['worker_id'])
            ->where('assignable_type', $validated['assignable_type'])
            ->where('assignable_id', $validated['assignable_id'])
            ->where('is_current', true)
            ->update(['is_current' => false, 'assigned_to' => $validated['assigned_from']]);

        $validated['assigned_by'] = $request->user()->id;
        $validated['is_current'] = !isset($validated['assigned_to']) || $validated['assigned_to'] >= now();

        $assignment = StaffAssignment::create($validated);

        return response()->json(['data' => $assignment->load('worker', 'assignable', 'assigner')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $assignment = StaffAssignment::with(['worker', 'assignable', 'assigner'])->findOrFail($id);
        return response()->json(['data' => $assignment]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $assignment = StaffAssignment::findOrFail($id);

        $validated = $request->validate([
            'role' => 'nullable|string|max:255',
            'core_responsibilities' => 'nullable|string',
            'assigned_from' => 'sometimes|date',
            'assigned_to' => 'nullable|date|after:assigned_from',
            'notes' => 'nullable|string',
        ]);

        // Update is_current based on assigned_to
        if (isset($validated['assigned_to'])) {
            $validated['is_current'] = $validated['assigned_to'] >= now();
        }

        $assignment->update($validated);

        return response()->json(['data' => $assignment->load('worker', 'assignable', 'assigner')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $assignment = StaffAssignment::findOrFail($id);
        $assignment->delete();

        return response()->json(null, 204);
    }

    /**
     * End a current assignment (re-assign or remove staff)
     */
    public function endAssignment(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $assignment = StaffAssignment::findOrFail($id);

        $validated = $request->validate([
            'assigned_to' => 'nullable|date|after:assigned_from',
        ]);

        $endDate = $validated['assigned_to'] ?? now();
        $assignment->endAssignment($endDate);

        return response()->json(['data' => $assignment->load('worker', 'assignable', 'assigner')]);
    }
}
