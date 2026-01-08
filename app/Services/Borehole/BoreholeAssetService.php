<?php

namespace App\Services\Borehole;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Site;
use App\Services\Asset\AssetCodeGeneratorService;

class BoreholeAssetService
{
    public function __construct(
        protected AssetCodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create an Asset record for a borehole when not provided.
     *
     * @param int $farmId
     * @param int $siteId
     * @param string $boreholeName
     * @param array $assetData Optional asset data from the form
     * @return Asset
     */
    public function createAssetForBorehole(int $farmId, int $siteId, string $boreholeName, array $assetData = []): Asset
    {
        // Resolve BOREHOLE category (global or per farm)
        // Use provided category_id if available, otherwise default to BOREHOLE
        $categoryId = $assetData['asset_category_id'] ?? null;
        
        if ($categoryId) {
            $category = AssetCategory::find($categoryId);
        } else {
            $category = AssetCategory::where('code', 'BOREHOLE')->first();
            if (!$category) {
                $category = AssetCategory::create([
                    'code' => 'BOREHOLE',
                    'name' => 'Borehole',
                    'is_active' => true,
                ]);
            }
        }

        $site = Site::find($siteId);
        $locationText = $site ? $site->name : null;

        // Generate asset code if not provided in assetData
        $assetCode = $assetData['asset_code'] ?? $this->codeGenerator->generate($farmId, $category->id);

        // Map asset data keys (already with 'asset_' prefix removed by controller) to asset model fields
        $assetFields = [
            'farm_id' => $farmId,
            'asset_category_id' => $category->id,
            'asset_code' => $assetCode,
            'name' => $assetData['name'] ?? $boreholeName,
            'description' => $assetData['description'] ?? null,
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
            'location_text' => $assetData['location_text'] ?? $locationText,
            'gps_lat' => $assetData['gps_lat'] ?? null,
            'gps_lng' => $assetData['gps_lng'] ?? null,
            'is_trackable' => $assetData['is_trackable'] ?? false,
            'created_by' => auth()->id(),
        ];

        // Remove null values to use database defaults where appropriate
        $assetFields = array_filter($assetFields, fn($value) => $value !== null);

        return Asset::create($assetFields);
    }
}


