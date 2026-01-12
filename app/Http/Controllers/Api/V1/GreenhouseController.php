<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Greenhouse;
use App\Models\Site;
use App\Http\Requests\StoreGreenhouseRequest;
use App\Http\Requests\UpdateGreenhouseRequest;
use App\Http\Resources\GreenhouseResource;
use App\Services\Greenhouse\GreenhouseAssetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GreenhouseController extends Controller
{
    public function __construct(
        protected GreenhouseAssetService $greenhouseAssetService
    ){}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Greenhouse::with(['farm', 'site', 'creator', 'asset.category']);

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

        // Create asset if track_as_asset is checked (MUST have asset record in Asset table)
        $trackAsAsset = $request->boolean('track_as_asset', false);
        
        if ($trackAsAsset) {
            // Always create a new asset record when tracking as asset
            // Extract asset data from validated array (keys prefixed with 'asset_')
            $assetData = [];
            foreach ($validated as $key => $value) {
                if (str_starts_with($key, 'asset_')) {
                    // Remove 'asset_' prefix for the service
                    $assetKey = substr($key, 6); // Remove 'asset_' (6 characters)
                    $assetData[$assetKey] = $value;
                }
            }
            
            // Include GPS coordinates from greenhouse if available
            if (isset($validated['latitude'])) {
                $assetData['asset_gps_lat'] = $validated['latitude'];
            }
            if (isset($validated['longitude'])) {
                $assetData['asset_gps_lng'] = $validated['longitude'];
            }
            
            // Create asset record - this item MUST be in the Asset table
            $asset = $this->greenhouseAssetService->createAssetForGreenhouse(
                $site->farm_id, 
                $validated['site_id'], 
                $validated['name'],
                $assetData
            );
            $greenhouse->asset_id = $asset->id;
            $greenhouse->save();
        } else {
            // If track_as_asset is false, ensure no asset link
            $greenhouse->asset_id = null;
            $greenhouse->save();
        }

        return (new GreenhouseResource($greenhouse->load('farm', 'site', 'creator', 'asset.category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $greenhouse = Greenhouse::with(['farm', 'site', 'creator', 'asset.category', 'bellPepperCycles', 'boreholes'])
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

        // Handle asset creation/update if track_as_asset is checked
        $trackAsAsset = $request->boolean('track_as_asset', false);
        
        if ($trackAsAsset && !$greenhouse->asset_id) {
            // Create new asset if tracking as asset but no asset exists
            $site = $greenhouse->site;
            $assetData = [];
            foreach ($validated as $key => $value) {
                if (str_starts_with($key, 'asset_')) {
                    $assetKey = substr($key, 6);
                    $assetData[$assetKey] = $value;
                }
            }
            
            // Include GPS coordinates from greenhouse if available
            if (isset($validated['latitude'])) {
                $assetData['asset_gps_lat'] = $validated['latitude'];
            }
            if (isset($validated['longitude'])) {
                $assetData['asset_gps_lng'] = $validated['longitude'];
            }
            
            $asset = $this->greenhouseAssetService->createAssetForGreenhouse(
                $site->farm_id,
                $greenhouse->site_id,
                $greenhouse->name,
                $assetData
            );
            $greenhouse->asset_id = $asset->id;
            $greenhouse->save();
        } elseif (!$trackAsAsset && $greenhouse->asset_id) {
            // Remove asset link if untracking
            $greenhouse->asset_id = null;
            $greenhouse->save();
        }

        return (new GreenhouseResource($greenhouse->load('farm', 'site', 'creator', 'asset.category')))->response();
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
