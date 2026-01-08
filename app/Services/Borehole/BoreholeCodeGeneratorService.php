<?php

namespace App\Services\Borehole;

use App\Models\Borehole;

class BoreholeCodeGeneratorService
{
    /**
     * Generate a unique borehole code for a given site.
     *
     * @param int $siteId
     * @return string Format: BH-{first3chars}-001, BH-{first3chars}-002, etc. (per site)
     */
    public function generate(int $siteId): string
    {
        // Get the site to extract its name
        $site = \App\Models\Site::find($siteId);
        if (!$site) {
            throw new \InvalidArgumentException("Site with ID {$siteId} not found");
        }
        
        // Use site name (not code) to generate the prefix
        // Extract first 3 alphanumeric characters from anywhere in the name (uppercase)
        $siteName = $site->name;
        // Remove all non-alphanumeric characters and take first 3
        $alphanumericOnly = preg_replace('/[^A-Z0-9]/', '', strtoupper($siteName));
        $siteCodePrefix = substr($alphanumericOnly, 0, 3);
        
        // If name doesn't have enough alphanumeric chars, pad with X
        if (strlen($siteCodePrefix) < 3) {
            $siteCodePrefix = str_pad($siteCodePrefix, 3, 'X', STR_PAD_RIGHT);
        }
        
        $prefix = 'BH-' . $siteCodePrefix;
        
        // Find the highest existing number for this site with this prefix
        // Include soft-deleted records since unique constraint applies to all records
        // Check both borehole_code and code columns (for backward compatibility)
        $boreholes = Borehole::withTrashed()
            ->where('site_id', $siteId)
            ->where(function($query) use ($prefix) {
                $query->where(function($q) use ($prefix) {
                    $q->whereNotNull('borehole_code')
                      ->where('borehole_code', 'like', $prefix . '-%');
                })->orWhere(function($q) use ($prefix) {
                    $q->whereNotNull('code')
                      ->where('code', 'like', $prefix . '-%');
                });
            })
            ->get()
            ->map(function($bh) {
                // Prefer borehole_code, fallback to code
                return $bh->borehole_code ?? $bh->code;
            })
            ->filter()
            ->toArray();
        
        $maxNumber = 0;
        // Pattern: BH-XXX-001, BH-XXX-002, etc.
        $pattern = '/^' . preg_quote($prefix, '/') . '-(\d+)$/';
        foreach ($boreholes as $code) {
            if (preg_match($pattern, $code, $matches)) {
                $number = (int)$matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }
        
        $nextNumber = $maxNumber + 1;
        
        // Ensure the code is unique (double-check)
        // Check both borehole_code and code columns, and include soft-deleted records
        // since the unique constraint applies to all records
        $proposedCode = sprintf('%s-%03d', $prefix, $nextNumber);
        $exists = Borehole::withTrashed()
            ->where('site_id', $siteId)
            ->where(function($query) use ($proposedCode) {
                $query->where('borehole_code', $proposedCode)
                      ->orWhere('code', $proposedCode);
            })
            ->exists();
        
        // If it exists, try next number
        while ($exists) {
            $nextNumber++;
            $proposedCode = sprintf('%s-%03d', $prefix, $nextNumber);
            $exists = Borehole::withTrashed()
                ->where('site_id', $siteId)
                ->where(function($query) use ($proposedCode) {
                    $query->where('borehole_code', $proposedCode)
                          ->orWhere('code', $proposedCode);
                })
                ->exists();
        }
        
        return $proposedCode;
    }

    /**
     * Validate that a borehole code is unique for a site.
     *
     * @param string $code
     * @param int $siteId
     * @param int|null $excludeBoreholeId
     * @return bool
     */
    public function isUnique(string $code, int $siteId, ?int $excludeBoreholeId = null): bool
    {
        $query = Borehole::where('site_id', $siteId)
            ->where('borehole_code', $code);

        if ($excludeBoreholeId) {
            $query->where('id', '!=', $excludeBoreholeId);
        }

        return !$query->exists();
    }
}

