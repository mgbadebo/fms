<?php

namespace App\Services\Site;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\Asset\AssetCodeGeneratorService;

class SiteAssetService
{
    public function __construct(
        protected AssetCodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create an Asset record for a site when not provided.
     *
     * @param int $farmId
     * @param string $siteName
     * @param array $assetData Optional asset data from the form
     * @return Asset
     */
    public function createAssetForSite(int $farmId, string $siteName, array $assetData = []): Asset
    {
        // Resolve SITE/BUILDING category (global or per farm)
        // Use provided category_id if available (prefix removed by controller), otherwise default to BUILDING
        $categoryId = $assetData['category_id'] ?? null;
        
        if ($categoryId) {
            $category = AssetCategory::find($categoryId);
        } else {
            $category = AssetCategory::where('code', 'BUILDING')->first();
            if (!$category) {
                $category = AssetCategory::create([
                    'code' => 'BUILDING',
                    'name' => 'Building/Site',
                    'is_active' => true,
                ]);
            }
        }

        $locationText = $siteName;

        // Generate asset code if not provided in assetData
        $assetCode = $assetData['code'] ?? $this->codeGenerator->generate($farmId, $category->id);

        // Map asset data keys (already with 'asset_' prefix removed by controller) to asset model fields
        // Note: Some keys from controller may have 'asset_' prefix for location/gps (controller adds them back)
        $assetFields = [
            'farm_id' => $farmId,
            'asset_category_id' => $category->id,
            'asset_code' => $assetCode,
            'name' => $assetData['name'] ?? $siteName,
            'description' => $assetData['description'] ?? ($assetData['asset_description'] ?? null),
            'status' => $assetData['status'] ?? 'ACTIVE',
            'acquisition_type' => $assetData['acquisition_type'] ?? null,
            'purchase_date' => $assetData['purchase_date'] ?? null,
            'purchase_cost' => $assetData['purchase_cost'] ?? null,
            'currency' => $assetData['currency'] ?? null,
            'supplier_name' => $assetData['supplier_name'] ?? null,
            'serial_number' => $assetData['serial_number'] ?? null,
            'model' => $assetData['model'] ?? null,
            'manufacturer' => $assetData['manufacturer'] ?? null,
            'year_of_make' => $assetData['year_of_make'] ?? null,
            'warranty_expiry' => $assetData['warranty_expiry'] ?? null,
            'location_text' => $assetData['asset_location_text'] ?? ($assetData['location_text'] ?? $locationText),
            'gps_lat' => $assetData['asset_gps_lat'] ?? ($assetData['gps_lat'] ?? null),
            'gps_lng' => $assetData['asset_gps_lng'] ?? ($assetData['gps_lng'] ?? null),
            'is_trackable' => $assetData['is_trackable'] ?? false,
            'created_by' => auth()->id(),
        ];

        // Remove null values to use database defaults where appropriate
        $assetFields = array_filter($assetFields, fn($value) => $value !== null);

        return Asset::create($assetFields);
    }
}

