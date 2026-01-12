<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);
        
        $query = Product::query();
        
        $user = $request->user();
        if (!$user->hasRole('ADMIN')) {
            $userFarmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($userFarmIds)) {
                return response()->json(['data' => []]);
            }
            $query->whereIn('farm_id', $userFarmIds);
        } elseif ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $products = $query->orderBy('name')->paginate(20);
        return ProductResource::collection($products)->response();
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Product::class);
        
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit_of_measure' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);
        
        // Verify user belongs to the farm
        $user = $request->user();
        if (!$user->hasRole('ADMIN')) {
            if (!$user->farms()->where('farms.id', $validated['farm_id'])->exists()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }
        
        // Check unique code per farm
        if (Product::where('farm_id', $validated['farm_id'])
            ->where('code', $validated['code'])
            ->exists()) {
            return response()->json([
                'message' => 'A product with this code already exists for this farm.'
            ], 422);
        }
        
        $product = Product::create($validated);
        
        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        Gate::authorize('view', $product);
        
        return (new ProductResource($product))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        Gate::authorize('update', $product);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit_of_measure' => 'sometimes|string|max:50',
            'is_active' => 'boolean',
        ]);
        
        $product->update($validated);
        
        return (new ProductResource($product))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        Gate::authorize('delete', $product);
        
        $product->delete();
        
        return response()->json(null, 204);
    }
}
