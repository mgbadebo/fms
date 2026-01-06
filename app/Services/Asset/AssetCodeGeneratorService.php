<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\AssetCategory;

class AssetCodeGeneratorService
{
    /**
     * Generate a unique asset code for a given category and farm.
     *
     * @param int $farmId
     * @param int|null $categoryId
     * @return string
     */
    public function generate(int $farmId, ?int $categoryId = null): string
    {
        $prefix = $this->getPrefix($categoryId);
        
        // Find the highest existing number for this prefix and farm
        $lastAsset = Asset::where('farm_id', $farmId)
            ->where('asset_code', 'like', $prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING(asset_code, ' . (strlen($prefix) + 2) . ') AS UNSIGNED) DESC')
            ->first();

        if ($lastAsset && preg_match('/-(\d+)$/', $lastAsset->asset_code, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%05d', $prefix, $nextNumber);
    }

    /**
     * Get the prefix for an asset code based on category.
     *
     * @param int|null $categoryId
     * @return string
     */
    protected function getPrefix(?int $categoryId): string
    {
        if (!$categoryId) {
            return 'AST'; // Default prefix
        }

        $category = AssetCategory::find($categoryId);
        
        if (!$category) {
            return 'AST';
        }

        // Use first 2-3 letters of category code, uppercase
        $code = strtoupper($category->code);
        
        // If code is short, use it directly; otherwise take first 2-3 chars
        if (strlen($code) <= 3) {
            return $code;
        }

        // Take first 2-3 meaningful characters
        return substr($code, 0, 3);
    }

    /**
     * Validate that an asset code is unique for a farm.
     *
     * @param string $code
     * @param int $farmId
     * @param int|null $excludeAssetId
     * @return bool
     */
    public function isUnique(string $code, int $farmId, ?int $excludeAssetId = null): bool
    {
        $query = Asset::where('farm_id', $farmId)
            ->where('asset_code', $code);

        if ($excludeAssetId) {
            $query->where('id', '!=', $excludeAssetId);
        }

        return !$query->exists();
    }
}
