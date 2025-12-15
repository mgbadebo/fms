<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GariWasteLoss extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'gari_production_batch_id',
        'gari_inventory_id',
        'loss_date',
        'loss_type',
        'gari_type',
        'packaging_type',
        'quantity_kg',
        'quantity_units',
        'cost_per_kg',
        'total_loss_value',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loss_date' => 'date',
            'quantity_kg' => 'decimal:2',
            'cost_per_kg' => 'decimal:2',
            'total_loss_value' => 'decimal:2',
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

    public function gariInventory()
    {
        return $this->belongsTo(GariInventory::class);
    }
}

