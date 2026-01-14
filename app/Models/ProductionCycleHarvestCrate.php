<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\Harvest\HarvestTotalsService;

class ProductionCycleHarvestCrate extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'harvest_record_id',
        'storage_location_id',
        'grade',
        'crate_number',
        'weight_kg',
        'weighed_at',
        'weighed_by',
        'label_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'weighed_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($crate) {
            // Derive farm_id from harvest_record
            if ($crate->harvest_record_id && !$crate->farm_id) {
                $record = ProductionCycleHarvestRecord::find($crate->harvest_record_id);
                if ($record) {
                    $crate->farm_id = $record->farm_id;
                }
            }
            
            // Auto-assign crate_number if not provided
            if (!$crate->crate_number && $crate->harvest_record_id) {
                $maxCrate = self::where('harvest_record_id', $crate->harvest_record_id)
                    ->max('crate_number');
                $crate->crate_number = ($maxCrate ?? 0) + 1;
            }
            
            // Set weighed_at if not provided
            if (!$crate->weighed_at) {
                $crate->weighed_at = now();
            }
            
            // Set weighed_by if not provided
            if (!$crate->weighed_by && auth()->check()) {
                $crate->weighed_by = auth()->id();
            }
        });

        static::saved(function ($crate) {
            // Recalculate totals on harvest record
            if ($crate->harvestRecord) {
                app(HarvestTotalsService::class)->recalculate($crate->harvestRecord);
            }
        });

        static::deleted(function ($crate) {
            // Recalculate totals on harvest record
            if ($crate->harvestRecord) {
                app(HarvestTotalsService::class)->recalculate($crate->harvestRecord);
            }
        });
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function harvestRecord()
    {
        return $this->belongsTo(ProductionCycleHarvestRecord::class, 'harvest_record_id');
    }

    public function weigher()
    {
        return $this->belongsTo(User::class, 'weighed_by');
    }

    public function storageLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'storage_location_id');
    }
}
