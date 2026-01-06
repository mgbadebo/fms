<?php

namespace App\Services\Greenhouse;

use App\Models\Greenhouse;

class GreenhouseCodeGeneratorService
{
    /**
     * Generate a unique greenhouse code for a given site.
     *
     * @param int $siteId
     * @return string Format: GH-{first3chars}-001, GH-{first3chars}-002, etc. (per site)
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
        
        $prefix = 'GH-' . $siteCodePrefix;
        
        // Find the highest existing number for this site with this prefix
        // Include soft-deleted records since unique constraint applies to all records
        // Include both greenhouse_code and code columns (for backward compatibility)
        $greenhouses = Greenhouse::withTrashed()
            ->where('site_id', $siteId)
            ->where(function($query) use ($prefix) {
                $query->where(function($q) use ($prefix) {
                    $q->whereNotNull('greenhouse_code')
                      ->where('greenhouse_code', 'like', $prefix . '-%');
                })->orWhere(function($q) use ($prefix) {
                    $q->whereNotNull('code')
                      ->where('code', 'like', $prefix . '-%');
                });
            })
            ->get()
            ->map(function($gh) {
                // Prefer greenhouse_code, fallback to code
                return $gh->greenhouse_code ?? $gh->code;
            })
            ->filter()
            ->toArray();
        
        $maxNumber = 0;
        // Pattern: GH-XXX-001, GH-XXX-002, etc.
        $pattern = '/^' . preg_quote($prefix, '/') . '-(\d+)$/';
        foreach ($greenhouses as $code) {
            if (preg_match($pattern, $code, $matches)) {
                $number = (int)$matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }
        
        $nextNumber = $maxNumber + 1;
        
        // Ensure the code is unique (double-check)
        // Check both greenhouse_code and code columns, and include soft-deleted records
        // since the unique constraint applies to all records
        $proposedCode = sprintf('%s-%03d', $prefix, $nextNumber);
        $exists = Greenhouse::withTrashed()
            ->where('site_id', $siteId)
            ->where(function($query) use ($proposedCode) {
                $query->where('greenhouse_code', $proposedCode)
                      ->orWhere('code', $proposedCode);
            })
            ->exists();
        
        // If it exists, try next number
        while ($exists) {
            $nextNumber++;
            $proposedCode = sprintf('%s-%03d', $prefix, $nextNumber);
            $exists = Greenhouse::withTrashed()
                ->where('site_id', $siteId)
                ->where(function($query) use ($proposedCode) {
                    $query->where('greenhouse_code', $proposedCode)
                          ->orWhere('code', $proposedCode);
                })
                ->exists();
        }
        
        return $proposedCode;
    }

    /**
     * Validate that a greenhouse code is unique for a site.
     *
     * @param string $code
     * @param int $siteId
     * @param int|null $excludeGreenhouseId
     * @return bool
     */
    public function isUnique(string $code, int $siteId, ?int $excludeGreenhouseId = null): bool
    {
        $query = Greenhouse::where('site_id', $siteId)
            ->where('greenhouse_code', $code);

        if ($excludeGreenhouseId) {
            $query->where('id', '!=', $excludeGreenhouseId);
        }

        return !$query->exists();
    }
}

