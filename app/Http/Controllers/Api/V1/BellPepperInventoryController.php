<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductionCycleHarvestCrate;
use App\Models\SalesOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BellPepperInventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get all crates with relationships
        $query = ProductionCycleHarvestCrate::with([
            'harvestRecord.productionCycle.greenhouse',
            'harvestRecord.productionCycle.greenhouse.site',
            'harvestRecord.productionCycle.greenhouse.site.farm',
            'storageLocation',
            'weigher'
        ]);

        // Apply farm scoping
        if ($user && !$user->hasRole('ADMIN')) {
            $farmIds = $user->farms()->pluck('farms.id');
            $query->whereIn('farm_id', $farmIds);
        }

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('greenhouse_id')) {
            $query->whereHas('harvestRecord.productionCycle', function ($q) use ($request) {
                $q->where('greenhouse_id', $request->greenhouse_id);
            });
        }

        if ($request->has('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->has('storage_location_id')) {
            if ($request->storage_location_id === 'unassigned') {
                $query->whereNull('storage_location_id');
            } else {
                $query->where('storage_location_id', $request->storage_location_id);
            }
        }

        if ($request->has('show_unassigned_only') && $request->show_unassigned_only) {
            $query->whereNull('storage_location_id');
        }

        $crates = $query->orderBy('created_at', 'desc')->get();

        // Calculate sold quantities per crate
        $crateIds = $crates->pluck('id');
        
        // Get sales order items that reference harvest records containing these crates
        $harvestRecordIds = $crates->pluck('harvest_record_id')->unique();
        
        $soldQuantities = SalesOrderItem::whereIn('harvest_record_id', $harvestRecordIds)
            ->selectRaw('harvest_record_id, SUM(quantity) as sold_quantity')
            ->groupBy('harvest_record_id')
            ->pluck('sold_quantity', 'harvest_record_id');

        // Build inventory items
        $inventory = [];
        $harvestRecordGroups = $crates->groupBy('harvest_record_id');

        foreach ($harvestRecordGroups as $harvestRecordId => $recordCrates) {
            $harvestRecord = $recordCrates->first()->harvestRecord;
            $soldQty = (float)($soldQuantities[$harvestRecordId] ?? 0);
            
            // Group crates by grade and storage location
            $groupedCrates = $recordCrates->groupBy(function ($crate) {
                return ($crate->storage_location_id ?? 'no_location') . '_' . $crate->grade;
            });

            foreach ($groupedCrates as $key => $groupCrates) {
                $firstCrate = $groupCrates->first();
                $totalWeight = $groupCrates->sum('weight_kg');
                $crateCount = $groupCrates->count();

                // For now, we'll show all crates as available inventory
                // In a more sophisticated system, you'd track which specific crates were sold
                $availableWeight = $totalWeight;

                $inventory[] = [
                    'id' => $firstCrate->id,
                    'harvest_record_id' => $harvestRecordId,
                    'harvest_date' => $harvestRecord->harvest_date?->format('Y-m-d'),
                    'production_cycle' => $harvestRecord->productionCycle ? [
                        'id' => $harvestRecord->productionCycle->id,
                        'code' => $harvestRecord->productionCycle->production_cycle_code,
                    ] : null,
                    'greenhouse' => $harvestRecord->greenhouse ? [
                        'id' => $harvestRecord->greenhouse->id,
                        'name' => $harvestRecord->greenhouse->name,
                    ] : null,
                    'site' => $harvestRecord->site ? [
                        'id' => $harvestRecord->site->id,
                        'name' => $harvestRecord->site->name,
                    ] : null,
                    'farm' => $harvestRecord->farm ? [
                        'id' => $harvestRecord->farm->id,
                        'name' => $harvestRecord->farm->name,
                    ] : null,
                    'grade' => $firstCrate->grade,
                    'crate_count' => $crateCount,
                    'total_weight_kg' => $totalWeight,
                    'available_weight_kg' => $availableWeight,
                    'storage_location' => $firstCrate->storageLocation ? [
                        'id' => $firstCrate->storageLocation->id,
                        'name' => $firstCrate->storageLocation->name,
                        'type' => $firstCrate->storageLocation->type,
                    ] : null,
                    'weighed_at' => $firstCrate->weighed_at?->format('Y-m-d H:i:s'),
                    'weighed_by' => $firstCrate->weigher ? [
                        'id' => $firstCrate->weigher->id,
                        'name' => $firstCrate->weigher->name,
                    ] : null,
                    'crates' => $groupCrates->map(function ($crate) {
                        return [
                            'id' => $crate->id,
                            'crate_number' => $crate->crate_number,
                            'weight_kg' => $crate->weight_kg,
                        ];
                    })->values(),
                ];
            }
        }

        // Apply additional filters
        if ($request->has('grade')) {
            $inventory = array_filter($inventory, function ($item) use ($request) {
                return $item['grade'] === $request->grade;
            });
        }

        if ($request->has('storage_location_id') && $request->storage_location_id !== 'unassigned') {
            $inventory = array_filter($inventory, function ($item) use ($request) {
                return $item['storage_location'] && $item['storage_location']['id'] == $request->storage_location_id;
            });
        }

        if ($request->has('show_unassigned_only') && $request->show_unassigned_only) {
            $inventory = array_filter($inventory, function ($item) {
                return !$item['storage_location'];
            });
        }

        // Sort by harvest date descending
        usort($inventory, function ($a, $b) {
            return strcmp($b['harvest_date'] ?? '', $a['harvest_date'] ?? '');
        });

        return response()->json([
            'success' => true,
            'data' => array_values($inventory),
        ]);
    }
}
