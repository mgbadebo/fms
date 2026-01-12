<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GreenhouseProductionCycle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($cycle) {
            // Derive farm_id and site_id from greenhouse if not set
            if (!$cycle->farm_id && $cycle->greenhouse_id) {
                $greenhouse = \App\Models\Greenhouse::find($cycle->greenhouse_id);
                if ($greenhouse) {
                    $cycle->farm_id = $greenhouse->farm_id;
                    $cycle->site_id = $greenhouse->site_id;
                }
            }
            
            // Generate production cycle code if not provided
            if (!$cycle->production_cycle_code && $cycle->greenhouse_id) {
                $codeGenerator = app(\App\Services\ProductionCycle\ProductionCycleCodeGeneratorService::class);
                $cycle->production_cycle_code = $codeGenerator->generate($cycle->greenhouse_id);
            }
            
            // Set created_by if not provided
            if (!$cycle->created_by && auth()->check()) {
                $cycle->created_by = auth()->id();
            }
        });
        
        static::updating(function ($cycle) {
            // If greenhouse_id changes, re-derive farm_id and site_id
            if ($cycle->isDirty('greenhouse_id') && $cycle->greenhouse_id) {
                $greenhouse = \App\Models\Greenhouse::find($cycle->greenhouse_id);
                if ($greenhouse) {
                    $cycle->farm_id = $greenhouse->farm_id;
                    $cycle->site_id = $greenhouse->site_id;
                }
            }
        });
    }

    protected $fillable = [
        'farm_id',
        'site_id',
        'greenhouse_id',
        'season_id',
        'production_cycle_code',
        'crop',
        'variety',
        'cycle_status',
        // Section 1: Planting & Establishment
        'planting_date',
        'establishment_method',
        'seed_supplier_name',
        'seed_batch_number',
        'nursery_start_date',
        'transplant_date',
        'plant_spacing_cm',
        'row_spacing_cm',
        'plant_density_per_sqm',
        'initial_plant_count',
        // Section 2: Growing medium & setup
        'cropping_system',
        'medium_type',
        'bed_count',
        'bench_count',
        'mulching_used',
        'support_system',
        // Section 3: Environmental targets
        'target_day_temperature_c',
        'target_night_temperature_c',
        'target_humidity_percent',
        'target_light_hours',
        'ventilation_strategy',
        'shade_net_percentage',
        // Management
        'responsible_supervisor_user_id',
        'created_by',
        'started_at',
        'ended_at',
        'notes',
        'target_total_yield_kg',
        'target_yield_per_plant_kg',
        'target_grade_a_pct',
        'target_grade_b_pct',
        'target_grade_c_pct',
    ];

    protected function casts(): array
    {
        return [
            'planting_date' => 'date',
            'nursery_start_date' => 'date',
            'transplant_date' => 'date',
            'plant_spacing_cm' => 'decimal:2',
            'row_spacing_cm' => 'decimal:2',
            'plant_density_per_sqm' => 'decimal:2',
            'mulching_used' => 'boolean',
            'target_day_temperature_c' => 'decimal:2',
            'target_night_temperature_c' => 'decimal:2',
            'target_humidity_percent' => 'decimal:2',
            'target_light_hours' => 'decimal:2',
            'shade_net_percentage' => 'decimal:2',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'target_total_yield_kg' => 'decimal:2',
            'target_yield_per_plant_kg' => 'decimal:3',
            'target_grade_a_pct' => 'decimal:2',
            'target_grade_b_pct' => 'decimal:2',
            'target_grade_c_pct' => 'decimal:2',
        ];
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

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function responsibleSupervisor()
    {
        return $this->belongsTo(User::class, 'responsible_supervisor_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dailyLogs()
    {
        return $this->hasMany(ProductionCycleDailyLog::class);
    }

    public function alerts()
    {
        return $this->hasMany(ProductionCycleAlert::class);
    }

    public function harvestRecords()
    {
        return $this->hasMany(ProductionCycleHarvestRecord::class, 'production_cycle_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('cycle_status', ['ACTIVE', 'HARVESTING']);
    }

    // Check if only one ACTIVE/HARVESTING cycle exists for this greenhouse
    public static function hasActiveCycle(int $greenhouseId): bool
    {
        return self::where('greenhouse_id', $greenhouseId)
            ->whereIn('cycle_status', ['ACTIVE', 'HARVESTING'])
            ->exists();
    }
}
