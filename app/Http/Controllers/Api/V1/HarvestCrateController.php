<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCrateRequest;
use App\Http\Requests\UpdateCrateRequest;
use App\Http\Resources\HarvestCrateResource;
use App\Models\ProductionCycleHarvestCrate;
use App\Models\ProductionCycleHarvestRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HarvestCrateController extends Controller
{
    public function index(Request $request, string $harvestRecordId): JsonResponse
    {
        $record = ProductionCycleHarvestRecord::findOrFail($harvestRecordId);
        Gate::authorize('view', $record);
        
        $crates = ProductionCycleHarvestCrate::with(['weigher', 'storageLocation'])
            ->where('harvest_record_id', $record->id)
            ->orderBy('crate_number')
            ->get();
        
        return HarvestCrateResource::collection($crates)->response();
    }

    public function store(AddCrateRequest $request, string $harvestRecordId): JsonResponse
    {
        $record = ProductionCycleHarvestRecord::findOrFail($harvestRecordId);
        Gate::authorize('update', $record);
        
        $validated = $request->validated();
        $crateCount = $validated['crate_count'] ?? 1;
        $totalWeight = $validated['total_weight_kg'] ?? $validated['weight_kg'] ?? 0;
        $weightPerCrate = $totalWeight / $crateCount;
        
        // Get the next crate number for this harvest record
        $nextCrateNumber = ProductionCycleHarvestCrate::where('harvest_record_id', $record->id)
            ->max('crate_number') ?? 0;
        
        $createdCrates = [];
        $weighedBy = $validated['weighed_by'] ?? $request->user()->id;
        
        // Create multiple crates if crate_count > 1
        for ($i = 0; $i < $crateCount; $i++) {
            $crateData = [
                'harvest_record_id' => $record->id,
                'farm_id' => $record->farm_id,
                'grade' => $validated['grade'],
                'crate_number' => $nextCrateNumber + $i + 1,
                'weight_kg' => $weightPerCrate,
                'weighed_by' => $weighedBy,
                'weighed_at' => $validated['weighed_at'] ?? now(),
                'storage_location_id' => $validated['storage_location_id'] ?? null,
                'label_code' => $validated['label_code'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];
            
            $crate = ProductionCycleHarvestCrate::create($crateData);
            $createdCrates[] = $crate;
        }
        
        // Totals will be recalculated automatically via model boot
        
        // Return the first crate (or all if only one)
        if (count($createdCrates) === 1) {
            return (new HarvestCrateResource($createdCrates[0]->load(['weigher', 'storageLocation'])))->response()->setStatusCode(201);
        }
        
        // Return collection if multiple crates created
        $loadedCrates = collect($createdCrates)->map(function ($crate) {
            return $crate->load(['weigher', 'storageLocation']);
        });
        return HarvestCrateResource::collection($loadedCrates)->response()->setStatusCode(201);
    }

    public function update(UpdateCrateRequest $request, string $id): JsonResponse
    {
        $crate = ProductionCycleHarvestCrate::findOrFail($id);
        Gate::authorize('update', $crate);
        
        $crate->update($request->validated());
        
        // Totals will be recalculated automatically via model boot
        
        return (new HarvestCrateResource($crate->load(['weigher', 'storageLocation'])))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $crate = ProductionCycleHarvestCrate::findOrFail($id);
        Gate::authorize('delete', $crate);
        
        // Only DRAFT harvest records can have crates deleted (unless override permission)
        if ($crate->harvestRecord && $crate->harvestRecord->status !== 'DRAFT') {
            $user = auth()->user();
            if (!$user->hasRole('ADMIN') && !$user->can('harvest.override_status')) {
                return response()->json([
                    'message' => 'Crates can only be deleted from DRAFT harvest records.'
                ], 422);
            }
        }
        
        $crate->delete();
        
        // Totals will be recalculated automatically via model boot
        
        return response()->json(null, 204);
    }
}
