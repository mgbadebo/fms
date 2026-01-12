<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityTypeResource;
use App\Models\ActivityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActivityTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ActivityType::class);
        
        $query = ActivityType::query();
        
        // Filter by farm (required for non-admin)
        $user = $request->user();
        if (!$user->hasRole('ADMIN')) {
            $userFarmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($userFarmIds)) {
                return response()->json(['data' => []]);
            }
            $query->whereIn('farm_id', $userFarmIds);
        } elseif ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $types = $query->orderBy('name')->paginate(20);
        return ActivityTypeResource::collection($types)->response();
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', ActivityType::class);
        
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'requires_quantity' => 'boolean',
            'requires_time_range' => 'boolean',
            'requires_inputs' => 'boolean',
            'requires_photos' => 'boolean',
            'schema' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        
        // Verify user belongs to the farm
        $user = $request->user();
        if (!$user->hasRole('ADMIN')) {
            if (!$user->farms()->where('farms.id', $validated['farm_id'])->exists()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }
        
        // Check unique code per farm
        if (ActivityType::where('farm_id', $validated['farm_id'])
            ->where('code', $validated['code'])
            ->exists()) {
            return response()->json([
                'message' => 'An activity type with this code already exists for this farm.'
            ], 422);
        }
        
        $type = ActivityType::create($validated);
        
        return (new ActivityTypeResource($type))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $type = ActivityType::findOrFail($id);
        Gate::authorize('view', $type);
        
        return (new ActivityTypeResource($type))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $type = ActivityType::findOrFail($id);
        Gate::authorize('update', $type);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'requires_quantity' => 'boolean',
            'requires_time_range' => 'boolean',
            'requires_inputs' => 'boolean',
            'requires_photos' => 'boolean',
            'schema' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        
        $type->update($validated);
        
        return (new ActivityTypeResource($type))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $type = ActivityType::findOrFail($id);
        Gate::authorize('delete', $type);
        
        // Soft delete (set is_active=false) instead of hard delete
        $type->update(['is_active' => false]);
        
        return response()->json(null, 204);
    }
}
