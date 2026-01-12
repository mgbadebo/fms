<?php

namespace App\Services\ProductionCycle;

use App\Models\Greenhouse;
use Illuminate\Support\Str;

class ProductionCycleCodeGeneratorService
{
    /**
     * Generate a unique production cycle code for a greenhouse.
     */
    public function generate(int $greenhouseId): string
    {
        $greenhouse = Greenhouse::findOrFail($greenhouseId);
        $site = $greenhouse->site;
        
        // Format: PC-{SITE_CODE}-{RANDOM}
        $siteCode = $site ? strtoupper(substr($site->code ?? $site->name, 0, 3)) : 'GH';
        $random = strtoupper(Str::random(6));
        
        $code = "PC-{$siteCode}-{$random}";
        
        // Ensure uniqueness
        $counter = 1;
        while (\App\Models\GreenhouseProductionCycle::where('production_cycle_code', $code)->exists()) {
            $code = "PC-{$siteCode}-{$random}-{$counter}";
            $counter++;
        }
        
        return $code;
    }
}
