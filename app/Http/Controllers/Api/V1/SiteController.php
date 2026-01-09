<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\Site\SiteAssetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function __construct(
        protected SiteAssetService $siteAssetService
    ){}
    /**
     * Check if user has admin permission
     */
    private function checkAdminPermission(): ?JsonResponse
    {
        $user = request()->user();
        if (!$user || !$user->hasRole('ADMIN')) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }
        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        // Admin can see all sites; others can only see sites from their farms
        $query = Site::with(['farm', 'asset']);

        if (!$user->hasRole('ADMIN')) {
            // Filter sites by user's farm membership
            $userFarmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($userFarmIds)) {
                // User has no farms, return empty result
                return response()->json([
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                    'last_page' => 1,
                ]);
            }
            $query->whereIn('farm_id', $userFarmIds);
        }
        
        // Apply additional filters (these apply to both admin and non-admin)

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->input('per_page', 20);
        $sites = $query->orderBy('name')->paginate($perPage);
        return response()->json($sites);
    }

    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $trackAsAsset = $request->boolean('track_as_asset', false);
        
        $rules = [
            'farm_id' => 'nullable|exists:farms,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:sites,code',
            'type' => 'required|exists:site_types,code',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
            'total_area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
            'track_as_asset' => 'boolean',
        ];
        
        // If tracking as asset, add asset field validation
        if ($trackAsAsset) {
            $rules = array_merge($rules, [
                'asset_category_id' => 'nullable|exists:asset_categories,id',
                'asset_description' => 'nullable|string',
                'asset_acquisition_type' => 'nullable|in:PURCHASED,LEASED,RENTED,DONATED',
                'asset_purchase_date' => 'nullable|date',
                'asset_purchase_cost' => 'nullable|numeric|min:0',
                'asset_currency' => 'nullable|string|size:3',
                'asset_supplier_name' => 'nullable|string|max:255',
                'asset_serial_number' => 'nullable|string|max:255',
                'asset_model' => 'nullable|string|max:255',
                'asset_manufacturer' => 'nullable|string|max:255',
                'asset_year_of_make' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'asset_warranty_expiry' => 'nullable|date',
                'asset_is_trackable' => 'boolean',
            ]);
        }
        
        $validated = $request->validate($rules);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $siteType = \App\Models\SiteType::where('code', $validated['type'])->first();
            $prefix = $siteType?->code_prefix ?? 'ST';
            $validated['code'] = $prefix . '-' . strtoupper(Str::random(8));
        }

        $validated['area_unit'] = $validated['area_unit'] ?? 'hectares';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $site = Site::create($validated);

        // Create asset only if track_as_asset is checked
        if ($trackAsAsset && empty($validated['asset_id'])) {
            // Extract asset data from validated array (keys prefixed with 'asset_')
            $assetData = [];
            foreach ($validated as $key => $value) {
                if (str_starts_with($key, 'asset_')) {
                    // Remove 'asset_' prefix for the service
                    $assetKey = substr($key, 6); // Remove 'asset_' (6 characters)
                    $assetData[$assetKey] = $value;
                }
            }
            
            // Include GPS coordinates from site if available
            if (isset($validated['latitude'])) {
                $assetData['asset_gps_lat'] = $validated['latitude'];
            }
            if (isset($validated['longitude'])) {
                $assetData['asset_gps_lng'] = $validated['longitude'];
            }
            if (isset($validated['address'])) {
                $assetData['asset_location_text'] = $validated['address'];
            }
            
            $asset = $this->siteAssetService->createAssetForSite(
                $validated['farm_id'] ?? $site->farm_id, 
                $validated['name'],
                $assetData
            );
            $site->asset_id = $asset->id;
            $site->save();
        }

        return response()->json(['data' => $site->load('farm', 'asset')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $site = Site::with(['farm', 'asset', 'farmZones', 'greenhouses', 'factories'])->findOrFail($id);
        return response()->json(['data' => $site]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $site = Site::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:sites,code,' . $id,
            'type' => 'sometimes|exists:site_types,code',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
            'total_area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $site->update($validated);

        return response()->json(['data' => $site->load('farm', 'asset')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $site = Site::findOrFail($id);
        $site->delete();

        return response()->json(null, 204);
    }
}
