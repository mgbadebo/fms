<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCycleHarvestRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'site_id',
        'greenhouse_id',
        'production_cycle_id',
        'harvest_date',
        'status',
        'recorded_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'total_weight_kg_a',
        'total_weight_kg_b',
        'total_weight_kg_c',
        'total_weight_kg_total',
        'crate_count_a',
        'crate_count_b',
        'crate_count_c',
        'crate_count_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'total_weight_kg_a' => 'decimal:2',
            'total_weight_kg_b' => 'decimal:2',
            'total_weight_kg_c' => 'decimal:2',
            'total_weight_kg_total' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($record) {
            // Derive farm_id, site_id, greenhouse_id from production_cycle
            if ($record->production_cycle_id && !$record->farm_id) {
                $cycle = GreenhouseProductionCycle::find($record->production_cycle_id);
                if ($cycle) {
                    $record->farm_id = $cycle->farm_id;
                    $record->site_id = $cycle->site_id;
                    $record->greenhouse_id = $cycle->greenhouse_id;
                }
            }
            
            // Set recorded_by if not provided
            if (!$record->recorded_by && auth()->check()) {
                $record->recorded_by = auth()->id();
            }
        });

        static::updating(function ($record) {
            // Re-derive if production_cycle_id changes
            if ($record->isDirty('production_cycle_id') && $record->production_cycle_id) {
                $cycle = GreenhouseProductionCycle::find($record->production_cycle_id);
                if ($cycle) {
                    $record->farm_id = $cycle->farm_id;
                    $record->site_id = $cycle->site_id;
                    $record->greenhouse_id = $cycle->greenhouse_id;
                }
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

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function productionCycle()
    {
        return $this->belongsTo(GreenhouseProductionCycle::class, 'production_cycle_id');
    }

    public function crates()
    {
        return $this->hasMany(ProductionCycleHarvestCrate::class, 'harvest_record_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'SUBMITTED');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }
}
