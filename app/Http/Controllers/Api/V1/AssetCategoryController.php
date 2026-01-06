<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssetCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AssetCategory::with(['parent', 'children']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->paginate(20);
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:asset_categories,id',
            'is_active' => 'boolean',
        ]);

        // Ensure code is unique per farm
        $exists = AssetCategory::where('farm_id', $validated['farm_id'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Category code already exists for this farm'
            ], 422);
        }

        $category = AssetCategory::create($validated);
        return response()->json(['data' => $category->load(['parent', 'children'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $category = AssetCategory::with(['parent', 'children', 'assets'])->findOrFail($id);
        return response()->json(['data' => $category]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = AssetCategory::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:asset_categories,id',
            'is_active' => 'boolean',
        ]);

        // Ensure code uniqueness if changed
        if (isset($validated['code']) && $validated['code'] !== $category->code) {
            $exists = AssetCategory::where('farm_id', $category->farm_id)
                ->where('code', $validated['code'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Category code already exists for this farm'
                ], 422);
            }
        }

        $category->update($validated);
        return response()->json(['data' => $category->load(['parent', 'children'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = AssetCategory::findOrFail($id);
        
        // Check if category has assets
        if ($category->assets()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing assets'
            ], 422);
        }

        $category->delete();
        return response()->json(null, 204);
    }
}
