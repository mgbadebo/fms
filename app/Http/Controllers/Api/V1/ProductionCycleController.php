<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductionCycleRequest;
use App\Http\Requests\UpdateProductionCycleRequest;
use App\Http\Resources\ProductionCycleResource;
use App\Models\GreenhouseProductionCycle;
use App\Models\Greenhouse;
use App\Services\ProductionCycle\ProductionCycleCodeGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductionCycleController extends Controller
{
    public function __construct(
        protected ProductionCycleCodeGeneratorService $codeGenerator
    ) {}
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', GreenhouseProductionCycle::class);
        
        $query = GreenhouseProductionCycle::with(['farm', 'site', 'greenhouse', 'season', 'responsibleSupervisor']);
        
        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }
        
        if ($request->has('greenhouse_id')) {
            $query->where('greenhouse_id', $request->greenhouse_id);
        }
        
        if ($request->has('cycle_status')) {
            $query->where('cycle_status', $request->cycle_status);
        }
        
        $perPage = $request->input('per_page', 20);
        $cycles = $query->orderBy('planting_date', 'desc')->paginate($perPage);
        return ProductionCycleResource::collection($cycles)->response();
    }

    public function store(StoreProductionCycleRequest $request): JsonResponse
    {
        Gate::authorize('create', GreenhouseProductionCycle::class);
        
        $validated = $request->validated();
        
        // Derive farm_id and site_id from greenhouse
        $greenhouse = Greenhouse::findOrFail($validated['greenhouse_id']);
        $validated['farm_id'] = $greenhouse->farm_id;
        $validated['site_id'] = $greenhouse->site_id;
        
        // Enforce only one ACTIVE/HARVESTING cycle per greenhouse
        if (GreenhouseProductionCycle::hasActiveCycle($greenhouse->id)) {
            return response()->json([
                'message' => 'This greenhouse already has an active or harvesting production cycle.'
            ], 422);
        }
        
        // Code and farm_id/site_id will be auto-generated in model boot method
        $validated['cycle_status'] = 'PLANNED';
        $validated['created_by'] = $request->user()->id;
        
        $cycle = GreenhouseProductionCycle::create($validated);
        
        // If season is provided and status is PLANNED, change it to ACTIVE
        if ($cycle->season_id) {
            $season = \App\Models\Season::find($cycle->season_id);
            if ($season && $season->status === 'PLANNED') {
                $season->update(['status' => 'ACTIVE']);
            }
        }
        
        return (new ProductionCycleResource($cycle->load('farm', 'site', 'greenhouse', 'season', 'responsibleSupervisor')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $cycle = GreenhouseProductionCycle::with([
            'farm', 'site', 'greenhouse', 'season', 'responsibleSupervisor', 'creator',
            'dailyLogs.items.activityType', 'dailyLogs.items.inputs', 'dailyLogs.items.photos'
        ])->findOrFail($id);
        
        Gate::authorize('view', $cycle);
        
        return (new ProductionCycleResource($cycle))->response();
    }

    public function update(UpdateProductionCycleRequest $request, string $id): JsonResponse
    {
        $cycle = GreenhouseProductionCycle::findOrFail($id);
        Gate::authorize('update', $cycle);
        
        $validated = $request->validated();
        
        // If status changes to ACTIVE, check for existing active cycle
        if (isset($validated['cycle_status']) && in_array($validated['cycle_status'], ['ACTIVE', 'HARVESTING'])) {
            $existingActive = GreenhouseProductionCycle::where('greenhouse_id', $cycle->greenhouse_id)
                ->whereIn('cycle_status', ['ACTIVE', 'HARVESTING'])
                ->where('id', '!=', $cycle->id)
                ->exists();
            
            if ($existingActive) {
                return response()->json([
                    'message' => 'This greenhouse already has an active or harvesting production cycle.'
                ], 422);
            }
            
            // Set started_at if transitioning to ACTIVE
            if ($validated['cycle_status'] === 'ACTIVE' && !$cycle->started_at) {
                $validated['started_at'] = now();
            }
        }
        
        // Set ended_at if completing
        if (isset($validated['cycle_status']) && $validated['cycle_status'] === 'COMPLETED' && !$cycle->ended_at) {
            $validated['ended_at'] = now();
        }
        
        $cycle->update($validated);
        
        return (new ProductionCycleResource($cycle->load('farm', 'site', 'greenhouse', 'season', 'responsibleSupervisor')))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $cycle = GreenhouseProductionCycle::findOrFail($id);
        Gate::authorize('delete', $cycle);
        
        $cycle->delete();
        
        return response()->json(null, 204);
    }

    public function start(Request $request, string $id): JsonResponse
    {
        $cycle = GreenhouseProductionCycle::findOrFail($id);
        Gate::authorize('update', $cycle);
        
        // Check for existing active cycle
        if (GreenhouseProductionCycle::hasActiveCycle($cycle->greenhouse_id)) {
            return response()->json([
                'message' => 'This greenhouse already has an active or harvesting production cycle.'
            ], 422);
        }
        
        $cycle->update([
            'cycle_status' => 'ACTIVE',
            'started_at' => now(),
        ]);
        
        return (new ProductionCycleResource($cycle->load('farm', 'site', 'greenhouse')))->response();
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $cycle = GreenhouseProductionCycle::findOrFail($id);
        Gate::authorize('update', $cycle);
        
        $cycle->update([
            'cycle_status' => 'COMPLETED',
            'ended_at' => now(),
        ]);
        
        return (new ProductionCycleResource($cycle->load('farm', 'site', 'greenhouse')))->response();
    }
}
