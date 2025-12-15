<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CropPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CropPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CropPlan::with(['farm', 'field', 'zone', 'season', 'crop']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('field_id')) {
            $query->where('field_id', $request->field_id);
        }

        if ($request->has('season_id')) {
            $query->where('season_id', $request->season_id);
        }

        $cropPlans = $query->orderBy('planned_planting_date', 'desc')->paginate(20);
        return response()->json($cropPlans);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'field_id' => 'required|exists:fields,id',
            'zone_id' => 'nullable|exists:zones,id',
            'season_id' => 'required|exists:seasons,id',
            'crop_id' => 'required|exists:crops,id',
            'planned_planting_date' => 'required|date',
            'planned_harvest_date' => 'nullable|date|after:planned_planting_date',
            'actual_planting_date' => 'nullable|date',
            'actual_harvest_date' => 'nullable|date',
            'area_planted' => 'nullable|numeric',
            'area_unit' => 'nullable|string|default:hectares',
            'status' => 'nullable|in:PLANNED,PLANTED,GROWING,HARVESTED,COMPLETED,CANCELLED',
            'notes' => 'nullable|string',
        ]);

        $cropPlan = CropPlan::create($validated);

        return response()->json(['data' => $cropPlan->load('farm', 'field', 'zone', 'season', 'crop')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $cropPlan = CropPlan::with([
            'farm', 'field', 'zone', 'season', 'crop',
            'harvestLots', 'scoutingLogs', 'inputApplications'
        ])->findOrFail($id);
        return response()->json(['data' => $cropPlan]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $cropPlan = CropPlan::findOrFail($id);

        $validated = $request->validate([
            'zone_id' => 'nullable|exists:zones,id',
            'crop_id' => 'sometimes|exists:crops,id',
            'planned_planting_date' => 'sometimes|date',
            'planned_harvest_date' => 'nullable|date|after:planned_planting_date',
            'actual_planting_date' => 'nullable|date',
            'actual_harvest_date' => 'nullable|date',
            'area_planted' => 'nullable|numeric',
            'area_unit' => 'nullable|string',
            'status' => 'nullable|in:PLANNED,PLANTED,GROWING,HARVESTED,COMPLETED,CANCELLED',
            'notes' => 'nullable|string',
        ]);

        $cropPlan->update($validated);

        return response()->json(['data' => $cropPlan->load('farm', 'field', 'zone', 'season', 'crop')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $cropPlan = CropPlan::findOrFail($id);
        $cropPlan->delete();

        return response()->json(null, 204);
    }
}

