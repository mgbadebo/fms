<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyLogRequest;
use App\Http\Requests\UpdateDailyLogRequest;
use App\Http\Resources\DailyLogResource;
use App\Models\GreenhouseProductionCycle;
use App\Models\ProductionCycleDailyLog;
use App\Models\ProductionCycleDailyLogItem;
use App\Models\ProductionCycleDailyLogItemInput;
use App\Models\ProductionCycleDailyLogItemPhoto;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class DailyLogController extends Controller
{
    public function index(Request $request, string $productionCycleId): JsonResponse
    {
        $cycle = GreenhouseProductionCycle::findOrFail($productionCycleId);
        Gate::authorize('view', $cycle);
        
        $query = ProductionCycleDailyLog::with([
            'items.activityType',
            'items.inputs',
            'items.photos',
            'submittedBy',
            'creator'
        ])->where('production_cycle_id', $cycle->id);
        
        if ($request->has('from')) {
            $query->where('log_date', '>=', $request->from);
        }
        
        if ($request->has('to')) {
            $query->where('log_date', '<=', $request->to);
        }
        
        $logs = $query->orderBy('log_date', 'desc')->paginate(20);
        return DailyLogResource::collection($logs)->response();
    }

    public function indexByGreenhouse(Request $request, string $greenhouseId): JsonResponse
    {
        $greenhouse = \App\Models\Greenhouse::findOrFail($greenhouseId);
        Gate::authorize('view', $greenhouse);
        
        $query = ProductionCycleDailyLog::with([
            'items.activityType',
            'items.inputs',
            'items.photos',
            'submittedBy',
            'creator',
            'productionCycle'
        ])->where('greenhouse_id', $greenhouse->id);
        
        if ($request->has('from')) {
            $query->where('log_date', '>=', $request->from);
        }
        
        if ($request->has('to')) {
            $query->where('log_date', '<=', $request->to);
        }
        
        $logs = $query->orderBy('log_date', 'desc')->paginate(20);
        return DailyLogResource::collection($logs)->response();
    }

    public function store(StoreDailyLogRequest $request, string $productionCycleId): JsonResponse
    {
        $cycle = GreenhouseProductionCycle::findOrFail($productionCycleId);
        Gate::authorize('update', $cycle);
        
        // Ensure cycle is ACTIVE or HARVESTING
        if (!in_array($cycle->cycle_status, ['ACTIVE', 'HARVESTING'])) {
            return response()->json([
                'message' => 'Daily logs can only be created for ACTIVE or HARVESTING production cycles.'
            ], 422);
        }
        
        $validated = $request->validated();
        
        // Derive farm_id, site_id, greenhouse_id from cycle
        $logData = [
            'farm_id' => $cycle->farm_id,
            'site_id' => $cycle->site_id,
            'greenhouse_id' => $cycle->greenhouse_id,
            'production_cycle_id' => $cycle->id,
            'log_date' => $validated['log_date'],
            'issues_notes' => $validated['issues_notes'] ?? null,
            'status' => 'DRAFT',
            'created_by' => $request->user()->id,
        ];
        
        // Create or update the log header (upsert)
        $log = ProductionCycleDailyLog::updateOrCreate(
            [
                'production_cycle_id' => $cycle->id,
                'log_date' => $validated['log_date'],
            ],
            $logData
        );
        
        // Delete existing items and recreate
        $log->items()->delete();
        
        // Create items
        foreach ($validated['items'] as $itemData) {
            $item = ProductionCycleDailyLogItem::create([
                'farm_id' => $cycle->farm_id,
                'daily_log_id' => $log->id,
                'activity_type_id' => $itemData['activity_type_id'],
                'performed_by_user_id' => $itemData['performed_by_user_id'] ?? null,
                'started_at' => isset($itemData['started_at']) ? Carbon::parse($itemData['started_at']) : null,
                'ended_at' => isset($itemData['ended_at']) ? Carbon::parse($itemData['ended_at']) : null,
                'quantity' => $itemData['quantity'] ?? null,
                'unit' => $itemData['unit'] ?? null,
                'notes' => $itemData['notes'] ?? null,
                'meta' => $itemData['meta'] ?? null,
            ]);
            
            // Create inputs if present
            if (isset($itemData['inputs']) && is_array($itemData['inputs'])) {
                foreach ($itemData['inputs'] as $inputData) {
                    ProductionCycleDailyLogItemInput::create([
                        'farm_id' => $cycle->farm_id,
                        'daily_log_item_id' => $item->id,
                        'input_item_id' => $inputData['input_item_id'] ?? null,
                        'input_name' => $inputData['input_name'] ?? null,
                        'quantity' => $inputData['quantity'],
                        'unit' => $inputData['unit'],
                        'notes' => $inputData['notes'] ?? null,
                    ]);
                }
            }
            
            // Handle photos if present (file upload would be handled separately)
            // For now, just store file paths if provided
            if (isset($itemData['photo_paths']) && is_array($itemData['photo_paths'])) {
                foreach ($itemData['photo_paths'] as $photoPath) {
                    ProductionCycleDailyLogItemPhoto::create([
                        'farm_id' => $cycle->farm_id,
                        'daily_log_item_id' => $item->id,
                        'file_path' => $photoPath,
                        'uploaded_by' => $request->user()->id,
                        'uploaded_at' => now(),
                    ]);
                }
            }
        }
        
        $log->load('items.activityType', 'items.inputs', 'items.photos');
        
        return (new DailyLogResource($log))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $log = ProductionCycleDailyLog::with([
            'items.activityType',
            'items.inputs.inputItem',
            'items.photos',
            'productionCycle',
            'submittedBy',
            'creator'
        ])->findOrFail($id);
        
        Gate::authorize('view', $log->productionCycle);
        
        return (new DailyLogResource($log))->response();
    }

    public function update(UpdateDailyLogRequest $request, string $id): JsonResponse
    {
        $log = ProductionCycleDailyLog::findOrFail($id);
        
        // Can only update DRAFT logs
        if ($log->status !== 'DRAFT') {
            return response()->json([
                'message' => 'Only DRAFT logs can be updated.'
            ], 422);
        }
        
        Gate::authorize('update', $log->productionCycle);
        
        $validated = $request->validated();
        
        // Update log header
        if (isset($validated['log_date'])) {
            $log->log_date = $validated['log_date'];
        }
        if (isset($validated['issues_notes'])) {
            $log->issues_notes = $validated['issues_notes'];
        }
        $log->save();
        
        // Update items if provided
        if (isset($validated['items'])) {
            $log->items()->delete();
            
            foreach ($validated['items'] as $itemData) {
                $item = ProductionCycleDailyLogItem::create([
                    'farm_id' => $log->farm_id,
                    'daily_log_id' => $log->id,
                    'activity_type_id' => $itemData['activity_type_id'],
                    'performed_by_user_id' => $itemData['performed_by_user_id'] ?? null,
                    'started_at' => isset($itemData['started_at']) ? Carbon::parse($itemData['started_at']) : null,
                    'ended_at' => isset($itemData['ended_at']) ? Carbon::parse($itemData['ended_at']) : null,
                    'quantity' => $itemData['quantity'] ?? null,
                    'unit' => $itemData['unit'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'meta' => $itemData['meta'] ?? null,
                ]);
                
                // Create inputs if present
                if (isset($itemData['inputs']) && is_array($itemData['inputs'])) {
                    foreach ($itemData['inputs'] as $inputData) {
                        ProductionCycleDailyLogItemInput::create([
                            'farm_id' => $log->farm_id,
                            'daily_log_item_id' => $item->id,
                            'input_item_id' => $inputData['input_item_id'] ?? null,
                            'input_name' => $inputData['input_name'] ?? null,
                            'quantity' => $inputData['quantity'],
                            'unit' => $inputData['unit'],
                            'notes' => $inputData['notes'] ?? null,
                        ]);
                    }
                }
            }
        }
        
        $log->load('items.activityType', 'items.inputs', 'items.photos');
        
        return (new DailyLogResource($log))->response();
    }

    public function submit(Request $request, string $id): JsonResponse
    {
        $log = ProductionCycleDailyLog::with('productionCycle.farm')->findOrFail($id);
        
        Gate::authorize('update', $log->productionCycle);
        
        // Can only submit DRAFT logs
        if ($log->status !== 'DRAFT') {
            return response()->json([
                'message' => 'Only DRAFT logs can be submitted.'
            ], 422);
        }
        
        $farm = $log->productionCycle->farm;
        $cutoffTime = $farm->daily_log_cutoff_time ?? '18:00:00';
        $timezone = $farm->default_timezone ?? config('app.timezone');
        
        // Get current time in farm timezone
        $now = Carbon::now($timezone);
        $cutoff = Carbon::parse($log->log_date->format('Y-m-d') . ' ' . $cutoffTime, $timezone);
        
        // Check if past cutoff (unless user has override permission)
        if ($now->gt($cutoff) && !$request->user()->can('daily_logs.override_cutoff')) {
            return response()->json([
                'message' => "Daily log submission deadline ({$cutoffTime}) has passed. Contact an administrator to override."
            ], 422);
        }
        
        $log->update([
            'status' => 'SUBMITTED',
            'submitted_at' => now(),
            'submitted_by' => $request->user()->id,
        ]);
        
        $log->load('items.activityType', 'items.inputs', 'items.photos');
        
        return (new DailyLogResource($log))->response();
    }
}
