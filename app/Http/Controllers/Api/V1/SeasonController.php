<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SeasonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Season::with('farm');

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $seasons = $query->orderBy('start_date', 'desc')->paginate(20);
        return response()->json($seasons);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'nullable|in:PLANNED,ACTIVE,COMPLETED,CANCELLED',
            'notes' => 'nullable|string',
        ]);

        $season = Season::create($validated);

        return response()->json(['data' => $season->load('farm')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $season = Season::with(['farm', 'cropPlans', 'harvestLots'])->findOrFail($id);
        return response()->json(['data' => $season]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $season = Season::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => [
                'nullable',
                'in:PLANNED,ACTIVE,COMPLETED,CANCELLED',
                function ($attribute, $value, $fail) use ($season) {
                    // Prevent cancelling or completing if there are active production cycles
                    if (in_array($value, ['CANCELLED', 'COMPLETED'])) {
                        if ($season->hasActiveProductionCycles()) {
                            $fail('Cannot ' . strtolower($value) . ' a season that has active production cycles. Please complete or abandon all production cycles first.');
                        }
                    }
                },
            ],
            'notes' => 'nullable|string',
        ]);

        $season->update($validated);

        return response()->json(['data' => $season->load('farm')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $season = Season::findOrFail($id);
        $season->delete();

        return response()->json(null, 204);
    }
}

