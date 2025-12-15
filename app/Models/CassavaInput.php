<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CassavaInput extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'gari_production_batch_id',
        'source_type',
        'harvest_lot_id',
        'field_id',
        'supplier_name',
        'supplier_contact',
        'purchase_date',
        'quantity_kg',
        'quantity_tonnes',
        'cost_per_kg',
        'cost_per_tonne',
        'total_cost',
        'variety',
        'quality_grade',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'quantity_kg' => 'decimal:2',
            'quantity_tonnes' => 'decimal:3',
            'cost_per_kg' => 'decimal:2',
            'cost_per_tonne' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function gariProductionBatch()
    {
        return $this->belongsTo(GariProductionBatch::class);
    }

    public function harvestLot()
    {
        return $this->belongsTo(HarvestLot::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    // Helper method for unit conversion
    public function getQuantityInKg()
    {
        // If tonnes is set, convert to kg (1 tonne = 1000 kg)
        if ($this->quantity_tonnes) {
            return $this->quantity_tonnes * 1000;
        }
        // Fallback to kg if tonnes not set
        return $this->quantity_kg ?? 0;
    }
}

