<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BellPepperHarvest;
use App\Models\BellPepperCycle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BellPepperHarvestController extends Controller
{
    /**
     * Check if user has permission to access harvest endpoints
     */
    private function checkPermission(string $action = 'view'): ?JsonResponse
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        // Admin has all permissions
        if ($user->hasRole('ADMIN')) {
            return null;
        }
        
        // Check for specific permission: bell-pepper.harvests.{action}
        $permissionName = "bell-pepper.harvests.{$action}";
        if (!$user->can($permissionName)) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }
        
        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkPermission('view');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $query = BellPepperHarvest::with(['farm', 'cycle', 'greenhouse', 'harvester']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('bell_pepper_cycle_id')) {
            $query->where('bell_pepper_cycle_id', $request->bell_pepper_cycle_id);
        }

        if ($request->has('greenhouse_id')) {
            $query->where('greenhouse_id', $request->greenhouse_id);
        }

        $harvests = $query->orderBy('harvest_date', 'desc')
            ->orderBy('harvest_number', 'asc')
            ->paginate(20);
        return response()->json($harvests);
    }

    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkPermission('create');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'bell_pepper_cycle_id' => 'required|exists:bell_pepper_cycles,id',
            'greenhouse_id' => 'required|exists:greenhouses,id',
            'harvester_id' => 'nullable|exists:users,id',
            'harvest_date' => 'required|date',
            'grade_a_kg' => 'required|numeric|min:0',
            'grade_b_kg' => 'required|numeric|min:0',
            'grade_c_kg' => 'required|numeric|min:0',
            'crates_count' => 'nullable|integer|min:0',
            'status' => 'nullable|in:HARVESTED,PACKED,IN_TRANSIT,DELIVERED,SOLD',
            'notes' => 'nullable|string',
        ]);

        // Validate harvest date is at least 70 days after cycle start date
        $cycle = BellPepperCycle::findOrFail($validated['bell_pepper_cycle_id']);
        $harvestDate = new \DateTime($validated['harvest_date']);
        $cycleStartDate = new \DateTime($cycle->start_date);
        
        if ($harvestDate < $cycleStartDate) {
            return response()->json([
                'message' => 'Harvest date cannot be earlier than the production cycle start date.',
                'errors' => [
                    'harvest_date' => ['Harvest date must be on or after the cycle start date: ' . $cycle->start_date]
                ]
            ], 422);
        }

        // Check if harvest date is at least 70 days after cycle start
        $daysDifference = $cycleStartDate->diff($harvestDate)->days;
        if ($daysDifference < 70) {
            $minHarvestDate = clone $cycleStartDate;
            $minHarvestDate->modify('+70 days');
            return response()->json([
                'message' => 'Harvest date must be at least 70 days after the production cycle start date.',
                'errors' => [
                    'harvest_date' => ['First harvest can be recorded from ' . $minHarvestDate->format('Y-m-d') . ' (70 days after cycle start)']
                ]
            ], 422);
        }

        // Set harvester_id from authenticated user if not provided
        if (!isset($validated['harvester_id']) && $request->user()) {
            $validated['harvester_id'] = $request->user()->id;
        }

        // Generate harvest code
        $validated['harvest_code'] = 'BP-HARV-' . strtoupper(Str::random(8));
        $validated['status'] = $validated['status'] ?? 'HARVESTED';

        // Auto-assign harvest number if not provided (count existing harvests for this cycle + 1)
        if (!isset($validated['harvest_number'])) {
            $existingHarvests = $cycle->harvests()->count();
            $validated['harvest_number'] = $existingHarvests + 1;
        }

        // Calculate total weight (will be auto-calculated by model, but set it here too)
        $totalWeight = (float)$validated['grade_a_kg'] + (float)$validated['grade_b_kg'] + (float)$validated['grade_c_kg'];

        // Auto-calculate crates if not provided (9-10kg per crate, use 9.5kg average)
        if (!isset($validated['crates_count']) || $validated['crates_count'] == 0) {
            $validated['crates_count'] = (int)ceil($totalWeight / 9.5);
        }

        $harvest = BellPepperHarvest::create($validated);

        // Update cycle actual yield
        $cycle = BellPepperCycle::findOrFail($validated['bell_pepper_cycle_id']);
        $cycle->actual_yield_kg = (float)$cycle->harvests()->sum('weight_kg');
        $cycle->calculateActualYieldPerSqm();
        $cycle->calculateYieldVariance();
        $cycle->save();

        return response()->json(['data' => $harvest->load('farm', 'cycle', 'greenhouse', 'harvester')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkPermission('view');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $harvest = BellPepperHarvest::with(['farm', 'cycle', 'greenhouse', 'harvester', 'sales'])->findOrFail($id);
        $harvest->remaining_weight = $harvest->getRemainingWeight();
        $harvest->remaining_weight_grade_a = $harvest->getRemainingWeightByGrade('A');
        $harvest->remaining_weight_grade_b = $harvest->getRemainingWeightByGrade('B');
        $harvest->remaining_weight_grade_c = $harvest->getRemainingWeightByGrade('C');
        $harvest->revenue = $harvest->getRevenue();
        $harvest->revenue_grade_a = $harvest->getRevenueByGrade('A');
        $harvest->revenue_grade_b = $harvest->getRevenueByGrade('B');
        $harvest->revenue_grade_c = $harvest->getRevenueByGrade('C');
        return response()->json(['data' => $harvest]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkPermission('update');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $harvest = BellPepperHarvest::findOrFail($id);

        $validated = $request->validate([
            'harvest_date' => 'sometimes|date',
            'harvest_number' => 'sometimes|integer|min:1',
            'grade_a_kg' => 'sometimes|numeric|min:0',
            'grade_b_kg' => 'sometimes|numeric|min:0',
            'grade_c_kg' => 'sometimes|numeric|min:0',
            'crates_count' => 'nullable|integer|min:0',
            'status' => 'nullable|in:HARVESTED,PACKED,IN_TRANSIT,DELIVERED,SOLD',
            'notes' => 'nullable|string',
        ]);

        // Validate harvest date if it's being updated
        if (isset($validated['harvest_date'])) {
            $cycle = $harvest->cycle;
            $harvestDate = new \DateTime($validated['harvest_date']);
            $cycleStartDate = new \DateTime($cycle->start_date);
            
            if ($harvestDate < $cycleStartDate) {
                return response()->json([
                    'message' => 'Harvest date cannot be earlier than the production cycle start date.',
                    'errors' => [
                        'harvest_date' => ['Harvest date must be on or after the cycle start date: ' . $cycle->start_date]
                    ]
                ], 422);
            }

            // Check if harvest date is at least 70 days after cycle start
            $daysDifference = $cycleStartDate->diff($harvestDate)->days;
            if ($daysDifference < 70) {
                $minDate = clone $cycleStartDate;
                $minDate->modify('+70 days');
                return response()->json([
                    'message' => 'Harvest date must be at least 70 days after the production cycle start date.',
                    'errors' => [
                        'harvest_date' => ['First harvest can be recorded from ' . $minDate->format('Y-m-d') . ' (70 days after cycle start)']
                    ]
                ], 422);
            }
        }

        $harvest->update($validated);

        // Update cycle actual yield if any grade weight changed
        if (isset($validated['grade_a_kg']) || isset($validated['grade_b_kg']) || isset($validated['grade_c_kg'])) {
            $cycle = $harvest->cycle;
            $cycle->actual_yield_kg = (float)$cycle->harvests()->sum('weight_kg');
            $cycle->calculateActualYieldPerSqm();
            $cycle->calculateYieldVariance();
            $cycle->save();
        }

        return response()->json(['data' => $harvest->load('farm', 'cycle', 'greenhouse', 'harvester')]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        // Only ADMIN can delete
        $user = $request->user();
        if (!$user || !$user->hasRole('ADMIN')) {
            return response()->json(['message' => 'Insufficient permissions. Only admins can delete harvests.'], 403);
        }

        $harvest = BellPepperHarvest::findOrFail($id);
        $cycle = $harvest->cycle;
        $harvest->delete();

        // Recalculate cycle yield
        $cycle->actual_yield_kg = (float)$cycle->harvests()->sum('weight_kg');
        $cycle->calculateActualYieldPerSqm();
        $cycle->calculateYieldVariance();
        $cycle->save();

        return response()->json(null, 204);
    }
}
