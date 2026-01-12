<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Greenhouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id', // Derived from site, not set directly by client
        'site_id',
        'asset_id', // Link to Asset if tracked as asset
        'code',
        'greenhouse_code',
        'kit_id',
        'kit_number',
        'name',
        'type',
        'status',
        'length',
        'width',
        'height',
        'total_area',
        'size_sqm',
        'orientation',
        'plant_capacity',
        'primary_crop_type',
        'cropping_system',
        'built_date',
        'construction_cost',
        'amortization_cycles',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'built_date' => 'date',
            'size_sqm' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'total_area' => 'decimal:2',
            'construction_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($greenhouse) {
            // Derive farm_id from site if not set
            if (!$greenhouse->farm_id && $greenhouse->site_id) {
                $site = \App\Models\Site::find($greenhouse->site_id);
                if ($site) {
                    $greenhouse->farm_id = $site->farm_id;
                }
            }
            
            // Set created_by if not provided
            if (!$greenhouse->created_by && auth()->check()) {
                $greenhouse->created_by = auth()->id();
            }
            
            // Set built_date if not provided (default to today)
            if (!$greenhouse->built_date) {
                $greenhouse->built_date = now()->toDateString();
            }
            
            // Compute area from length and width if provided
            $computedArea = null;
            if ($greenhouse->length && $greenhouse->width) {
                $computedArea = $greenhouse->length * $greenhouse->width;
            }
            
            // Set total_area if not provided
            if (!$greenhouse->total_area && $computedArea) {
                $greenhouse->total_area = $computedArea;
            }
            
            // Set size_sqm (required field) - use total_area, computed area, or default to 0
            if (!$greenhouse->size_sqm) {
                if ($greenhouse->total_area) {
                    $greenhouse->size_sqm = $greenhouse->total_area;
                } elseif ($computedArea) {
                    $greenhouse->size_sqm = $computedArea;
                    // Also set total_area if not set
                    if (!$greenhouse->total_area) {
                        $greenhouse->total_area = $computedArea;
                    }
                } else {
                    // Default to 0 if nothing is provided (shouldn't happen if length/width are required)
                    $greenhouse->size_sqm = 0;
                }
            }
            
            // Auto-generate greenhouse_code if not provided
            if (!$greenhouse->greenhouse_code && $greenhouse->site_id) {
                $codeGenerator = app(\App\Services\Greenhouse\GreenhouseCodeGeneratorService::class);
                $greenhouse->greenhouse_code = $codeGenerator->generate($greenhouse->site_id);
            }
            
            // Set code field (for backward compatibility with existing code column)
            // Always sync code with greenhouse_code to maintain compatibility
            if ($greenhouse->greenhouse_code) {
                $greenhouse->code = $greenhouse->greenhouse_code;
            }
            
            // Set greenhouse_code from code if not provided (for backward compatibility with old records)
            if (!$greenhouse->greenhouse_code && $greenhouse->code) {
                $greenhouse->greenhouse_code = $greenhouse->code;
            }
        });
        
        static::updating(function ($greenhouse) {
            // If site_id changes, re-derive farm_id
            if ($greenhouse->isDirty('site_id') && $greenhouse->site_id) {
                $site = \App\Models\Site::find($greenhouse->site_id);
                if ($site) {
                    $greenhouse->farm_id = $site->farm_id;
                }
            }
            
            // Recompute total_area if length or width changes
            if ($greenhouse->isDirty(['length', 'width']) && $greenhouse->length && $greenhouse->width) {
                $greenhouse->total_area = $greenhouse->length * $greenhouse->width;
            }
        });
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
    
    /**
     * Compute total area from length and width.
     */
    public function computeTotalArea(): float
    {
        if ($this->length && $this->width) {
            return (float)($this->length * $this->width);
        }
        return 0;
    }
    
    /**
     * Get total area (computed or stored).
     * Note: This is handled in the boot method and stored in the database.
     * This accessor ensures we always return a value even if not stored.
     */
    public function getTotalAreaAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        // If not stored, compute on the fly
        return $this->computeTotalArea();
    }

    // Staff assignments to this greenhouse
    public function staffAssignments()
    {
        return $this->morphMany(StaffAssignment::class, 'assignable');
    }

    public function bellPepperCycles()
    {
        return $this->hasMany(BellPepperCycle::class);
    }

    public function productionCycles()
    {
        return $this->hasMany(GreenhouseProductionCycle::class);
    }

    public function boreholes()
    {
        return $this->belongsToMany(Borehole::class, 'greenhouse_borehole');
    }


    // Calculate amortized cost per cycle
    public function getAmortizedCostPerCycle(): float
    {
        if ($this->amortization_cycles <= 0) {
            return 0;
        }
        return (float)($this->construction_cost / $this->amortization_cycles);
    }

    // Calculate total borehole amortized cost per cycle (sum of all linked boreholes)
    public function getBoreholeAmortizedCostPerCycle(): float
    {
        $total = 0;
        foreach ($this->boreholes as $borehole) {
            $total += $borehole->getAmortizedCostPerCycle();
        }
        return $total;
    }

    // Get all harvests for this greenhouse
    public function harvests()
    {
        return $this->hasManyThrough(BellPepperHarvest::class, BellPepperCycle::class);
    }

    // Calculate total revenue for this greenhouse across all cycles
    public function getTotalRevenue(): float
    {
        $total = 0;
        foreach ($this->bellPepperCycles as $cycle) {
            $total += $cycle->getTotalRevenue();
        }
        return $total;
    }

    // Calculate total revenue for a specific year
    public function getRevenueForYear(int $year): float
    {
        $total = 0;
        foreach ($this->bellPepperCycles()->whereYear('start_date', $year)->get() as $cycle) {
            $total += $cycle->getTotalRevenue();
        }
        return $total;
    }

    // Calculate total expenses for this greenhouse across all cycles
    public function getTotalExpenses(): float
    {
        $total = 0;
        foreach ($this->bellPepperCycles as $cycle) {
            $total += $cycle->getTotalCosts();
        }
        // Add amortized greenhouse and borehole costs
        $total += $this->getAmortizedCostPerCycle() * $this->bellPepperCycles()->count();
        $total += $this->getBoreholeAmortizedCostPerCycle() * $this->bellPepperCycles()->count();
        return $total;
    }

    // Calculate profit margin (revenue - expenses)
    public function getProfitMargin(): float
    {
        return $this->getTotalRevenue() - $this->getTotalExpenses();
    }

    // Calculate profit margin percentage
    public function getProfitMarginPercentage(): float
    {
        $revenue = $this->getTotalRevenue();
        if ($revenue == 0) {
            return 0;
        }
        return (($this->getProfitMargin() / $revenue) * 100);
    }
}
