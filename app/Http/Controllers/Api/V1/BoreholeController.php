<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Borehole;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreBoreholeRequest;
use App\Http\Requests\UpdateBoreholeRequest;
use App\Http\Resources\BoreholeResource;
use App\Services\Borehole\BoreholeAssetService;
use Illuminate\Support\Facades\Gate;

class BoreholeController extends Controller
{
    public function __construct(
        protected BoreholeAssetService $boreholeAssetService
    ){}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Borehole::class);
        $query = Borehole::with(['farm', 'site', 'asset.category'])
            ->when($request->site_id, fn($q) => $q->where('site_id', $request->site_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function($q) use ($request) {
                $search = $request->search;
                $q->where(function($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('borehole_code', 'like', "%{$search}%");
                });
            });

        $boreholes = $query->orderBy('name')->paginate(20);
        return BoreholeResource::collection($boreholes)->response();
    }

    public function store(StoreBoreholeRequest $request): JsonResponse
    {
        Gate::authorize('create', Borehole::class);
        $validated = $request->validated();
        // Derive farm from site
        $site = Site::findOrFail($validated['site_id']);
        $farmId = $site->farm_id;

        // Reject farm_id if present handled in request (prohibited)

        // Set derived farm_id and created_by
        $validated['farm_id'] = $farmId;
        $validated['created_by'] = $request->user()?->id;

        // Ensure borehole_code uniqueness per site will be enforced by validation and unique index

        $borehole = Borehole::create($validated);

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
            
            // Include GPS coordinates from borehole if available
            if (isset($validated['gps_lat'])) {
                $assetData['asset_gps_lat'] = $validated['gps_lat'];
            }
            if (isset($validated['gps_lng'])) {
                $assetData['asset_gps_lng'] = $validated['gps_lng'];
            }
            if (isset($validated['location_description'])) {
                $assetData['asset_location_text'] = $validated['location_description'];
            }
            
            // Create asset record - this item MUST be in the Asset table
            $asset = $this->boreholeAssetService->createAssetForBorehole(
                $farmId, 
                $validated['site_id'], 
                $validated['name'],
                $assetData
            );
            $borehole->asset_id = $asset->id;
            $borehole->save();
        } else {
            // If track_as_asset is false, ensure no asset link
            $borehole->asset_id = null;
            $borehole->save();
        }

        return (new BoreholeResource($borehole->load('farm','site','asset.category')))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $borehole = Borehole::with(['farm', 'site', 'asset.category'])->findOrFail($id);
        Gate::authorize('view', $borehole);
        return (new BoreholeResource($borehole))->response();
    }

    public function update(UpdateBoreholeRequest $request, string $id): JsonResponse
    {
        $borehole = Borehole::findOrFail($id);
        Gate::authorize('update', $borehole);

        $validated = $request->validated();

        // If site changes, re-derive farm
        if (isset($validated['site_id']) && $validated['site_id'] != $borehole->site_id) {
            $site = Site::findOrFail($validated['site_id']);
            $validated['farm_id'] = $site->farm_id;
        }

        $borehole->update($validated);

        // Handle asset creation/update if track_as_asset is checked
        $trackAsAsset = $request->boolean('track_as_asset', false);
        
        if ($trackAsAsset && !$borehole->asset_id) {
            // Create new asset if tracking as asset but no asset exists
            $assetData = [];
            foreach ($validated as $key => $value) {
                if (str_starts_with($key, 'asset_')) {
                    $assetKey = substr($key, 6);
                    $assetData[$assetKey] = $value;
                }
            }
            
            // Include GPS coordinates from borehole if available
            if (isset($validated['gps_lat'])) {
                $assetData['asset_gps_lat'] = $validated['gps_lat'];
            }
            if (isset($validated['gps_lng'])) {
                $assetData['asset_gps_lng'] = $validated['gps_lng'];
            }
            if (isset($validated['location_description'])) {
                $assetData['asset_location_text'] = $validated['location_description'];
            }
            
            $asset = $this->boreholeAssetService->createAssetForBorehole(
                $borehole->farm_id,
                $borehole->site_id,
                $borehole->name,
                $assetData
            );
            $borehole->asset_id = $asset->id;
            $borehole->save();
        } elseif (!$trackAsAsset && $borehole->asset_id) {
            // Remove asset link if untracking
            $borehole->asset_id = null;
            $borehole->save();
        }

        return (new BoreholeResource($borehole->load('farm','site','asset.category')))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $borehole = Borehole::findOrFail($id);
        Gate::authorize('delete', $borehole);
        $borehole->delete();

        return response()->json(null, 204);
    }
}
