<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GariInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gari_inventory';

    protected $fillable = [
        'farm_id',
        'gari_production_batch_id',
        'gari_type',
        'gari_grade',
        'packaging_type',
        'quantity_kg',
        'quantity_units',
        'location_id',
        'cost_per_kg',
        'total_cost',
        'status',
        'production_date',
        'expiry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'cost_per_kg' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'production_date' => 'date',
            'expiry_date' => 'date',
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

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class);
    }
}

