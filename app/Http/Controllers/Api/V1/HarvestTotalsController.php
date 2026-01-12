<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductionCycleHarvestRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionCycleHarvestRecord;

class HarvestTotalsController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        Gate::authorize('view', 'harvest.view');
        
        $request->validate([
            'farm_id' => 'sometimes|exists:farms,id',
            'date' => 'required|date',
        ]);
        
        $user = $request->user();
        $farmIds = [];
        
        if (!$user->hasRole('ADMIN')) {
            $farmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($farmIds)) {
                return response()->json(['data' => []]);
            }
        } elseif ($request->has('farm_id')) {
            $farmIds = [(int)$request->farm_id];
        }
        
        $date = $request->input('date');
        
        // Get harvest records for the date
        $query = ProductionCycleHarvestRecord::with('greenhouse')
            ->where('harvest_date', $date)
            ->where('status', '!=', 'DRAFT'); // Only submitted/approved records
        
        if (!empty($farmIds)) {
            $query->whereIn('farm_id', $farmIds);
        }
        
        $records = $query->get();
        
        // Group by greenhouse
        $perGreenhouse = [];
        $allTotals = [
            'a_kg' => 0,
            'b_kg' => 0,
            'c_kg' => 0,
            'total_kg' => 0,
            'crates_total' => 0,
        ];
        
        foreach ($records as $record) {
            $greenhouseId = $record->greenhouse_id;
            $greenhouseName = $record->greenhouse?->name ?? 'Unknown';
            
            if (!isset($perGreenhouse[$greenhouseId])) {
                $perGreenhouse[$greenhouseId] = [
                    'greenhouse_id' => $greenhouseId,
                    'greenhouse_name' => $greenhouseName,
                    'a_kg' => 0,
                    'b_kg' => 0,
                    'c_kg' => 0,
                    'total_kg' => 0,
                    'crates_total' => 0,
                ];
            }
            
            $perGreenhouse[$greenhouseId]['a_kg'] += (float)$record->total_weight_kg_a;
            $perGreenhouse[$greenhouseId]['b_kg'] += (float)$record->total_weight_kg_b;
            $perGreenhouse[$greenhouseId]['c_kg'] += (float)$record->total_weight_kg_c;
            $perGreenhouse[$greenhouseId]['total_kg'] += (float)$record->total_weight_kg_total;
            $perGreenhouse[$greenhouseId]['crates_total'] += (int)$record->crate_count_total;
            
            $allTotals['a_kg'] += (float)$record->total_weight_kg_a;
            $allTotals['b_kg'] += (float)$record->total_weight_kg_b;
            $allTotals['c_kg'] += (float)$record->total_weight_kg_c;
            $allTotals['total_kg'] += (float)$record->total_weight_kg_total;
            $allTotals['crates_total'] += (int)$record->crate_count_total;
        }
        
        // Round values
        foreach ($perGreenhouse as &$gh) {
            $gh['a_kg'] = round($gh['a_kg'], 2);
            $gh['b_kg'] = round($gh['b_kg'], 2);
            $gh['c_kg'] = round($gh['c_kg'], 2);
            $gh['total_kg'] = round($gh['total_kg'], 2);
        }
        
        $allTotals['a_kg'] = round($allTotals['a_kg'], 2);
        $allTotals['b_kg'] = round($allTotals['b_kg'], 2);
        $allTotals['c_kg'] = round($allTotals['c_kg'], 2);
        $allTotals['total_kg'] = round($allTotals['total_kg'], 2);
        
        return response()->json([
            'data' => [
                'date' => $date,
                'per_greenhouse' => array_values($perGreenhouse),
                'all_greenhouses_total' => $allTotals,
            ],
        ]);
    }
}
