<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HarvestLot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'crop_plan_id',
        'field_id',
        'zone_id',
        'season_id',
        'code',
        'harvested_at',
        'gross_weight',
        'net_weight',
        'weight_unit',
        'quality_grade',
        'storage_location_id',
        'traceability_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'harvested_at' => 'datetime',
            'gross_weight' => 'decimal:2',
            'net_weight' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($harvestLot) {
            if (empty($harvestLot->traceability_id)) {
                $harvestLot->traceability_id = 'HL-' . Str::upper(Str::random(12));
            }
            if (empty($harvestLot->code)) {
                $harvestLot->code = 'HL-' . date('Ymd') . '-' . Str::upper(Str::random(6));
            }
        });
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function cropPlan()
    {
        return $this->belongsTo(CropPlan::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function storageLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'storage_location_id');
    }

    public function storageContents()
    {
        return $this->hasMany(StorageContent::class);
    }

    public function weighingRecords()
    {
        return $this->morphMany(WeighingRecord::class, 'context');
    }

    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function printedLabels()
    {
        return $this->morphMany(PrintedLabel::class, 'target');
    }
}
