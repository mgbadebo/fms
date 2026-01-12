<?php

namespace App\Services\Harvest;

use App\Models\ProductionCycleHarvestRecord;
use App\Models\ProductionCycleHarvestCrate;

class HarvestTotalsService
{
    /**
     * Recalculate totals for a harvest record based on its crates.
     */
    public function recalculate(ProductionCycleHarvestRecord $record): void
    {
        $crates = ProductionCycleHarvestCrate::where('harvest_record_id', $record->id)->get();
        
        // Calculate totals by grade
        $totalA = 0;
        $totalB = 0;
        $totalC = 0;
        $countA = 0;
        $countB = 0;
        $countC = 0;
        
        foreach ($crates as $crate) {
            $weight = (float)$crate->weight_kg;
            
            switch ($crate->grade) {
                case 'A':
                    $totalA += $weight;
                    $countA++;
                    break;
                case 'B':
                    $totalB += $weight;
                    $countB++;
                    break;
                case 'C':
                    $totalC += $weight;
                    $countC++;
                    break;
            }
        }
        
        // Update record totals
        $record->total_weight_kg_a = round($totalA, 2);
        $record->total_weight_kg_b = round($totalB, 2);
        $record->total_weight_kg_c = round($totalC, 2);
        $record->total_weight_kg_total = round($totalA + $totalB + $totalC, 2);
        $record->crate_count_a = $countA;
        $record->crate_count_b = $countB;
        $record->crate_count_c = $countC;
        $record->crate_count_total = $countA + $countB + $countC;
        
        $record->save();
    }
}
