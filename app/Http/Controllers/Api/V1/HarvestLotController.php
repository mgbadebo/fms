<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HarvestLot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HarvestLotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HarvestLot::with(['farm', 'cropPlan', 'field', 'zone', 'season']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $harvestLots = $query->latest('harvested_at')->paginate(20);
        return response()->json($harvestLots);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'crop_plan_id' => 'nullable|exists:crop_plans,id',
            'field_id' => 'required|exists:fields,id',
            'zone_id' => 'nullable|exists:zones,id',
            'season_id' => 'required|exists:seasons,id',
            'code' => 'nullable|string|unique:harvest_lots,code',
            'harvested_at' => 'required|date',
            'gross_weight' => 'nullable|numeric',
            'net_weight' => 'nullable|numeric',
            'weight_unit' => 'nullable|string|default:kg',
            'quality_grade' => 'nullable|string',
            'storage_location_id' => 'nullable|exists:inventory_locations,id',
            'notes' => 'nullable|string',
        ]);

        $harvestLot = HarvestLot::create($validated);

        return response()->json(['data' => $harvestLot->load('farm', 'cropPlan', 'field', 'zone', 'season')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $harvestLot = HarvestLot::with([
            'farm', 'cropPlan', 'field', 'zone', 'season',
            'weighingRecords', 'storageContents', 'printedLabels'
        ])->findOrFail($id);
        return response()->json(['data' => $harvestLot]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $harvestLot = HarvestLot::findOrFail($id);

        $validated = $request->validate([
            'crop_plan_id' => 'nullable|exists:crop_plans,id',
            'field_id' => 'sometimes|exists:fields,id',
            'zone_id' => 'nullable|exists:zones,id',
            'season_id' => 'sometimes|exists:seasons,id',
            'code' => 'sometimes|string|unique:harvest_lots,code,' . $id,
            'harvested_at' => 'sometimes|date',
            'gross_weight' => 'nullable|numeric',
            'net_weight' => 'nullable|numeric',
            'weight_unit' => 'nullable|string',
            'quality_grade' => 'nullable|string',
            'storage_location_id' => 'nullable|exists:inventory_locations,id',
            'notes' => 'nullable|string',
        ]);

        $harvestLot->update($validated);

        return response()->json(['data' => $harvestLot->load('farm', 'cropPlan', 'field', 'zone', 'season')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $harvestLot = HarvestLot::findOrFail($id);
        $harvestLot->delete();

        return response()->json(null, 204);
    }
}
