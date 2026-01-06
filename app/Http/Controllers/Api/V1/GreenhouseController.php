<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Greenhouse;
use App\Models\Site;
use App\Http\Requests\StoreGreenhouseRequest;
use App\Http\Requests\UpdateGreenhouseRequest;
use App\Http\Resources\GreenhouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GreenhouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Greenhouse::with(['farm', 'site', 'creator']);

        // Filter by site_id
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by farm_id (for farm scoping)
        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('greenhouse_code', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $greenhouses = $query->orderBy('name')->paginate(20);
        
        return GreenhouseResource::collection($greenhouses)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGreenhouseRequest $request): JsonResponse
    {
        // Load the site to derive farm_id
        $site = Site::findOrFail($request->validated()['site_id']);
        
        // Prepare validated data
        $validated = $request->validated();
        
        // CRITICAL: Set farm_id from site (server-side, never from client)
        $validated['farm_id'] = $site->farm_id;
        
        // Set created_by
        $validated['created_by'] = auth()->id();
        
        // Compute total_area if length and width are provided
        if (isset($validated['length']) && isset($validated['width'])) {
            $validated['total_area'] = $validated['length'] * $validated['width'];
        }
        
        // Set greenhouse_code from code if not provided (for backward compatibility)
        if (!isset($validated['greenhouse_code']) && isset($validated['code'])) {
            $validated['greenhouse_code'] = $validated['code'];
        }
        
        // Remove greenhouse_code from validated if empty/null (will be auto-generated)
        if (empty($validated['greenhouse_code'])) {
            unset($validated['greenhouse_code']);
        }
        
        // Create the greenhouse (code will be auto-generated in model boot if not provided)
        $greenhouse = Greenhouse::create($validated);

        return (new GreenhouseResource($greenhouse->load('farm', 'site', 'creator')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $greenhouse = Greenhouse::with(['farm', 'site', 'creator', 'bellPepperCycles', 'boreholes'])
            ->findOrFail($id);
        
        return (new GreenhouseResource($greenhouse))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGreenhouseRequest $request, string $id): JsonResponse
    {
        $greenhouse = Greenhouse::findOrFail($id);
        
        $validated = $request->validated();
        
        // If site_id is being updated, re-derive farm_id
        if (isset($validated['site_id']) && $validated['site_id'] != $greenhouse->site_id) {
            $site = Site::findOrFail($validated['site_id']);
            $validated['farm_id'] = $site->farm_id;
        }
        
        // Recompute total_area if length or width changes
        if (isset($validated['length']) || isset($validated['width'])) {
            $length = $validated['length'] ?? $greenhouse->length;
            $width = $validated['width'] ?? $greenhouse->width;
            if ($length && $width) {
                $validated['total_area'] = $length * $width;
            }
        }
        
        $greenhouse->update($validated);

        return (new GreenhouseResource($greenhouse->load('farm', 'site', 'creator')))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $greenhouse = Greenhouse::findOrFail($id);
        
        // Soft delete (if soft deletes are enabled)
        $greenhouse->delete();

        return response()->json(null, 204);
    }
}
