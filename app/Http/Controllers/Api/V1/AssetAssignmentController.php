<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssetAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AssetAssignment::with(['asset', 'assignedTo', 'assignedBy']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->has('active')) {
            if ($request->boolean('active')) {
                $query->whereNull('returned_at');
            } else {
                $query->whereNotNull('returned_at');
            }
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->paginate(20);
        return response()->json($assignments);
    }
}
