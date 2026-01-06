<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\AssetDepreciationProfile;
use Carbon\Carbon;

class DepreciationService
{
    /**
     * Calculate depreciation schedule for an asset.
     *
     * @param Asset $asset
     * @param Carbon|null $toDate
     * @return array Array of monthly depreciation entries
     */
    public function calculateSchedule(Asset $asset, ?Carbon $toDate = null): array
    {
        $profile = $asset->depreciationProfile;
        
        if (!$profile || !$asset->purchase_cost) {
            return [];
        }

        $toDate = $toDate ?? now();
        $startDate = Carbon::parse($profile->start_date);
        
        if ($startDate->gt($toDate)) {
            return [];
        }

        $schedule = [];
        $currentDate = $startDate->copy();
        $bookValue = (float)$asset->purchase_cost;
        $salvageValue = (float)($profile->salvage_value ?? 0);
        $usefulLifeMonths = $profile->useful_life_months;

        if ($profile->method === 'STRAIGHT_LINE') {
            $monthlyDepreciation = ($bookValue - $salvageValue) / $usefulLifeMonths;
            
            while ($currentDate->lte($toDate) && count($schedule) < $usefulLifeMonths) {
                $schedule[] = [
                    'month' => $currentDate->format('Y-m'),
                    'date' => $currentDate->format('Y-m-d'),
                    'depreciation_amount' => round($monthlyDepreciation, 2),
                    'accumulated_depreciation' => round($monthlyDepreciation * (count($schedule) + 1), 2),
                    'book_value' => round($bookValue - ($monthlyDepreciation * (count($schedule) + 1)), 2),
                ];
                
                $currentDate->addMonth();
            }
        } else {
            // REDUCING_BALANCE method
            // Calculate monthly rate: r = 1 - (salvage/cost)^(1/n)
            // For simplicity, we use a fixed monthly percentage
            // More accurate: monthly_rate = 1 - (salvage_value / purchase_cost) ^ (1 / useful_life_months)
            if ($salvageValue > 0 && $bookValue > $salvageValue) {
                $monthlyRate = 1 - pow($salvageValue / $bookValue, 1 / $usefulLifeMonths);
            } else {
                // If no salvage value, use a reasonable depreciation rate
                // Aim to depreciate 90% over useful life
                $monthlyRate = 1 - pow(0.1, 1 / $usefulLifeMonths);
            }

            $accumulated = 0;
            
            while ($currentDate->lte($toDate) && count($schedule) < $usefulLifeMonths) {
                $depreciationAmount = $bookValue * $monthlyRate;
                $accumulated += $depreciationAmount;
                $bookValueAfter = $bookValue - $accumulated;
                
                // Ensure we don't go below salvage value
                if ($bookValueAfter < $salvageValue) {
                    $depreciationAmount = $bookValue - $salvageValue - ($accumulated - $depreciationAmount);
                    $accumulated = $bookValue - $salvageValue;
                    $bookValueAfter = $salvageValue;
                }

                $schedule[] = [
                    'month' => $currentDate->format('Y-m'),
                    'date' => $currentDate->format('Y-m-d'),
                    'depreciation_amount' => round($depreciationAmount, 2),
                    'accumulated_depreciation' => round($accumulated, 2),
                    'book_value' => round($bookValueAfter, 2),
                ];
                
                $bookValue = $bookValueAfter;
                $currentDate->addMonth();
            }
        }

        return $schedule;
    }

    /**
     * Get current book value of an asset.
     *
     * @param Asset $asset
     * @return float
     */
    public function getCurrentBookValue(Asset $asset): float
    {
        $schedule = $this->calculateSchedule($asset);
        
        if (empty($schedule)) {
            return (float)($asset->purchase_cost ?? 0);
        }

        $lastEntry = end($schedule);
        return $lastEntry['book_value'];
    }
}
