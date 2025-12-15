<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CropController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Crop::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $crops = $query->paginate(20);
        return response()->json($crops);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:crops,name',
            'category' => 'required|string',
            'scientific_name' => 'nullable|string|max:255',
            'default_maturity_days' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $crop = Crop::create($validated);

        return response()->json(['data' => $crop], 201);
    }

    public function show(string $id): JsonResponse
    {
        $crop = Crop::with(['cropPlans'])->findOrFail($id);
        return response()->json(['data' => $crop]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $crop = Crop::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:crops,name,' . $id,
            'category' => 'sometimes|string',
            'scientific_name' => 'nullable|string|max:255',
            'default_maturity_days' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $crop->update($validated);

        return response()->json(['data' => $crop]);
    }

    public function destroy(string $id): JsonResponse
    {
        $crop = Crop::findOrFail($id);
        $crop->delete();

        return response()->json(null, 204);
    }
}

