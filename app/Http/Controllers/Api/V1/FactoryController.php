<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\Site;
use App\Services\Factory\FactoryAssetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class FactoryController extends Controller
{
    public function __construct(
        protected FactoryAssetService $factoryAssetService
    ) {}

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
        // Allow reading factories without admin permission (needed for production batch creation)
        $query = Factory::with(['site', 'asset.category']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('farm_id')) {
            // Filter factories by farm through sites
            $query->whereHas('site', function ($q) use ($request) {
                $q->where('farm_id', $request->farm_id);
            });
        }

        if ($request->has('production_type')) {
            $query->where('production_type', $request->production_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $factories = $query->orderBy('name')->paginate(20);
        return response()->json($factories);
    }

    public function store(Request $request): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:factories,code',
            'production_type' => 'required|in:gari,other',
            'description' => 'nullable|string',
            'area_sqm' => 'nullable|numeric|min:0',
            'established_date' => 'nullable|date',
            'equipment' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
            // Asset fields
            'track_as_asset' => 'boolean',
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'asset_description' => 'nullable|string',
            'asset_acquisition_type' => 'nullable|string',
            'asset_purchase_date' => 'nullable|date',
            'asset_purchase_cost' => 'nullable|numeric|min:0',
            'asset_currency' => 'nullable|string|max:3',
            'asset_supplier_name' => 'nullable|string|max:255',
            'asset_serial_number' => 'nullable|string|max:255',
            'asset_model' => 'nullable|string|max:255',
            'asset_manufacturer' => 'nullable|string|max:255',
            'asset_year_of_make' => 'nullable|integer|min:1900',
            'asset_warranty_expiry' => 'nullable|date',
            'asset_is_trackable' => 'boolean',
        ]);

        // Load the site to get farm_id
        $site = Site::findOrFail($validated['site_id']);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $prefix = match($validated['production_type']) {
                'gari' => 'GARI-FT',
                default => 'FT'
            };
            $validated['code'] = $prefix . '-' . strtoupper(Str::random(8));
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        $factory = Factory::create($validated);

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
            
            // Create asset record - this item MUST be in the Asset table
            $asset = $this->factoryAssetService->createAssetForFactory(
                $site->farm_id, 
                $validated['site_id'], 
                $validated['name'],
                $assetData
            );
            $factory->asset_id = $asset->id;
            $factory->save();
        } else {
            // If track_as_asset is false, ensure no asset link
            $factory->asset_id = null;
            $factory->save();
        }

        return response()->json(['data' => $factory->load('site', 'asset.category')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $factory = Factory::with(['site', 'asset.category', 'staffAssignments.worker', 'gariProductionBatches'])->findOrFail($id);
        return response()->json(['data' => $factory]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $factory = Factory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:factories,code,' . $id,
            'production_type' => 'sometimes|in:gari,other',
            'description' => 'nullable|string',
            'area_sqm' => 'nullable|numeric|min:0',
            'established_date' => 'nullable|date',
            'equipment' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
            // Asset fields
            'track_as_asset' => 'boolean',
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'asset_description' => 'nullable|string',
            'asset_acquisition_type' => 'nullable|string',
            'asset_purchase_date' => 'nullable|date',
            'asset_purchase_cost' => 'nullable|numeric|min:0',
            'asset_currency' => 'nullable|string|max:3',
            'asset_supplier_name' => 'nullable|string|max:255',
            'asset_serial_number' => 'nullable|string|max:255',
            'asset_model' => 'nullable|string|max:255',
            'asset_manufacturer' => 'nullable|string|max:255',
            'asset_year_of_make' => 'nullable|integer|min:1900',
            'asset_warranty_expiry' => 'nullable|date',
            'asset_is_trackable' => 'boolean',
        ]);

        $factory->update($validated);

        // Handle asset creation/update if track_as_asset is checked
        $trackAsAsset = $request->boolean('track_as_asset', false);
        
        if ($trackAsAsset && !$factory->asset_id) {
            // Create new asset if tracking as asset but no asset exists
            $site = $factory->site;
            $assetData = [];
            foreach ($validated as $key => $value) {
                if (str_starts_with($key, 'asset_')) {
                    $assetKey = substr($key, 6);
                    $assetData[$assetKey] = $value;
                }
            }
            
            $asset = $this->factoryAssetService->createAssetForFactory(
                $site->farm_id,
                $factory->site_id,
                $factory->name,
                $assetData
            );
            $factory->asset_id = $asset->id;
            $factory->save();
        } elseif (!$trackAsAsset && $factory->asset_id) {
            // Remove asset link if untracking
            $factory->asset_id = null;
            $factory->save();
        }

        return response()->json(['data' => $factory->load('site', 'asset.category')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $permissionCheck = $this->checkAdminPermission();
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $factory = Factory::findOrFail($id);
        $factory->delete();

        return response()->json(null, 204);
    }
}
