<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'input_item_id',
        'inventory_location_id',
        'movement_type',
        'quantity',
        'unit',
        'unit_cost',
        'currency',
        'reference_type',
        'reference_id',
        'occurred_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function inputItem()
    {
        return $this->belongsTo(InputItem::class);
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
