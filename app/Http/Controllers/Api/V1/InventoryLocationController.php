<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InventoryLocation::query();

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $locations = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }
}
