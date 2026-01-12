<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHarvestRecordRequest;
use App\Http\Requests\UpdateHarvestRecordRequest;
use App\Http\Requests\SubmitHarvestRecordRequest;
use App\Http\Requests\ApproveHarvestRecordRequest;
use App\Http\Resources\HarvestRecordResource;
use App\Models\ProductionCycleHarvestRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\Harvest\HarvestTotalsService;

class HarvestRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProductionCycleHarvestRecord::class);
        
        $query = ProductionCycleHarvestRecord::with([
            'farm', 'site', 'greenhouse', 'productionCycle', 'recorder', 'approver'
        ]);
        
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
        
        if ($request->has('production_cycle_id')) {
            $query->where('production_cycle_id', $request->production_cycle_id);
        }
        
        if ($request->has('greenhouse_id')) {
            $query->where('greenhouse_id', $request->greenhouse_id);
        }
        
        if ($request->has('from')) {
            $query->where('harvest_date', '>=', $request->from);
        }
        
        if ($request->has('to')) {
            $query->where('harvest_date', '<=', $request->to);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $records = $query->orderBy('harvest_date', 'desc')->paginate(20);
        return HarvestRecordResource::collection($records)->response();
    }

    public function store(StoreHarvestRecordRequest $request): JsonResponse
    {
        Gate::authorize('create', ProductionCycleHarvestRecord::class);
        
        $validated = $request->validated();
        $validated['recorded_by'] = $request->user()->id;
        
        $record = ProductionCycleHarvestRecord::create($validated);
        
        return (new HarvestRecordResource($record->load('productionCycle', 'greenhouse', 'recorder')))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $record = ProductionCycleHarvestRecord::with([
            'farm', 'site', 'greenhouse', 'productionCycle', 
            'recorder', 'approver', 'crates.weigher'
        ])->findOrFail($id);
        
        Gate::authorize('view', $record);
        
        return (new HarvestRecordResource($record))->response();
    }

    public function update(UpdateHarvestRecordRequest $request, string $id): JsonResponse
    {
        $record = ProductionCycleHarvestRecord::findOrFail($id);
        Gate::authorize('update', $record);
        
        $record->update($request->validated());
        
        return (new HarvestRecordResource($record->load('productionCycle', 'greenhouse')))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $record = ProductionCycleHarvestRecord::findOrFail($id);
        Gate::authorize('delete', $record);
        
        // Only DRAFT records can be deleted
        if ($record->status !== 'DRAFT') {
            return response()->json([
                'message' => 'Only DRAFT harvest records can be deleted.'
            ], 422);
        }
        
        $record->delete();
        
        return response()->json(null, 204);
    }

    public function submit(SubmitHarvestRecordRequest $request, string $id): JsonResponse
    {
        $record = ProductionCycleHarvestRecord::findOrFail($id);
        Gate::authorize('update', $record);
        
        $record->update([
            'status' => 'SUBMITTED',
            'submitted_at' => now(),
        ]);
        
        return (new HarvestRecordResource($record->load('productionCycle', 'greenhouse')))->response();
    }

    public function approve(ApproveHarvestRecordRequest $request, string $id): JsonResponse
    {
        $record = ProductionCycleHarvestRecord::findOrFail($id);
        Gate::authorize('update', $record);
        
        $record->update([
            'status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        
        return (new HarvestRecordResource($record->load('productionCycle', 'greenhouse', 'approver')))->response();
    }
}
