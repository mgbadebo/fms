<?php

namespace App\Services\Farm;

use App\Models\Farm;

class FarmCodeGeneratorService
{
    /**
     * Generate a unique farm code.
     *
     * @return string Format: FARM-0001, FARM-0002, etc.
     */
    public function generate(): string
    {
        $prefix = 'FARM';
        
        // Find the highest existing number
        $lastFarm = Farm::where('farm_code', 'like', $prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING(farm_code, ' . (strlen($prefix) + 2) . ') AS UNSIGNED) DESC')
            ->first();

        if ($lastFarm && preg_match('/-(\d+)$/', $lastFarm->farm_code, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%04d', $prefix, $nextNumber);
    }
}

