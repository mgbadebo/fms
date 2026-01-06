<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarmRequest;
use App\Http\Resources\FarmResource;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FarmController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farms = Farm::with(['users', 'seasons', 'site', 'adminZone'])->paginate(20);
        return response()->json($farms);
    }

    public function store(StoreFarmRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Create the farm
        $farm = Farm::create($validated);
        
        // Attach the creating user to the farm if not already attached
        $user = $request->user();
        if ($user && !$farm->users->contains($user->id)) {
            $farm->users()->attach($user->id, ['role' => 'OWNER']);
        }

        return response()->json(['data' => new FarmResource($farm)], 201);
    }

    public function show(string $id): JsonResponse
    {
        $farm = Farm::with(['users', 'seasons', 'fields', 'cropPlans', 'site', 'adminZone'])->findOrFail($id);
        return response()->json(['data' => new FarmResource($farm)]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $farm = Farm::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'farm_type' => 'sometimes|in:CROP,LIVESTOCK,MIXED,AQUACULTURE,HORTICULTURE',
            'country' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'town' => 'sometimes|string|max:100',
            'default_currency' => 'sometimes|string|size:3',
            'default_unit_system' => 'sometimes|in:METRIC,IMPERIAL',
            'default_timezone' => 'sometimes|timezone',
            'accounting_method' => 'sometimes|in:CASH,ACCRUAL',
            'status' => 'sometimes|in:ACTIVE,INACTIVE,ARCHIVED',
            'site_id' => 'nullable|exists:sites,id',
            'admin_zone_id' => 'nullable|exists:admin_zones,id',
            'description' => 'nullable|string',
            'total_area' => 'nullable|numeric',
            'area_unit' => 'nullable|string',
            'is_active' => 'boolean',
            // Note: farm_code is not in validation - it's auto-generated and should not be manually updated
        ]);

        // Ensure farm_code exists (for existing farms created before this field was added)
        if (empty($farm->farm_code)) {
            $farm->farm_code = app(\App\Services\Farm\FarmCodeGeneratorService::class)->generate();
        }

        $farm->update($validated);

        // Refresh to get updated farm_code
        $farm->refresh();

        return response()->json(['data' => new FarmResource($farm)]);
    }

    public function destroy(string $id): JsonResponse
    {
        $farm = Farm::findOrFail($id);
        $farm->delete();

        return response()->json(null, 204);
    }
}
