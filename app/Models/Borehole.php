<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Borehole extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($borehole) {
            // Derive farm_id from site if not set
            if (!$borehole->farm_id && $borehole->site_id) {
                $site = \App\Models\Site::find($borehole->site_id);
                if ($site) {
                    $borehole->farm_id = $site->farm_id;
                }
            }
            
            // Set created_by if not provided
            if (!$borehole->created_by && auth()->check()) {
                $borehole->created_by = auth()->id();
            }
            
            // Auto-generate borehole_code if not provided
            if (!$borehole->borehole_code && $borehole->site_id) {
                $codeGenerator = app(\App\Services\Borehole\BoreholeCodeGeneratorService::class);
                $borehole->borehole_code = $codeGenerator->generate($borehole->site_id);
            }
            
            // Set code field (for backward compatibility with existing code column)
            // Always sync code with borehole_code to maintain compatibility
            if ($borehole->borehole_code) {
                $borehole->code = $borehole->borehole_code;
            }
            
            // Set borehole_code from code if not provided (for backward compatibility with old records)
            if (!$borehole->borehole_code && $borehole->code) {
                $borehole->borehole_code = $borehole->code;
            }
            
            // Set installed_date if not provided (for backward compatibility with old schema)
            // Use drilling_date if available, otherwise use today's date
            if (!$borehole->installed_date) {
                if ($borehole->drilling_date) {
                    $borehole->installed_date = $borehole->drilling_date;
                } else {
                    $borehole->installed_date = now()->toDateString();
                }
            }
        });
        
        static::updating(function ($borehole) {
            // If site_id changes, re-derive farm_id
            if ($borehole->isDirty('site_id') && $borehole->site_id) {
                $site = \App\Models\Site::find($borehole->site_id);
                if ($site) {
                    $borehole->farm_id = $site->farm_id;
                }
                
                // Regenerate borehole_code if site_id changes
                $codeGenerator = app(\App\Services\Borehole\BoreholeCodeGeneratorService::class);
                $borehole->borehole_code = $codeGenerator->generate($borehole->site_id);
                $borehole->code = $borehole->borehole_code;
            }
        });
    }

    protected $fillable = [
        'farm_id',
        'site_id',
        'borehole_code',
        'name',
        'status',
        'gps_lat',
        'gps_lng',
        'location_description',
        'depth_m',
        'static_water_level_m',
        'yield_m3_per_hr',
        'casing_diameter_mm',
        'drilling_date',
        'drilling_contractor',
        'borehole_type',
        'is_metered',
        'meter_reference',
        'next_water_test_due_at',
        'water_quality_notes',
        'asset_id',
        'pump_asset_id',
        'power_asset_id',
        'storage_tank_asset_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'drilling_date' => 'date',
            'next_water_test_due_at' => 'date',
            'installed_date' => 'date', // Old field, kept for backward compatibility
            'is_metered' => 'boolean',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function greenhouses()
    {
        return $this->belongsToMany(Greenhouse::class, 'greenhouse_borehole');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function pumpAsset()
    {
        return $this->belongsTo(Asset::class, 'pump_asset_id');
    }

    public function powerAsset()
    {
        return $this->belongsTo(Asset::class, 'power_asset_id');
    }

    public function storageTankAsset()
    {
        return $this->belongsTo(Asset::class, 'storage_tank_asset_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Deprecated legacy helper retained for BC; boreholes now tracked via Asset module
    public function getAmortizedCostPerCycle(): float
    {
        return 0.0;
    }
}
