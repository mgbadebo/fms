<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackagingMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'material_type',
        'size',
        'unit',
        'opening_balance',
        'quantity_purchased',
        'quantity_used',
        'closing_balance',
        'cost_per_unit',
        'total_cost',
        'location_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'quantity_purchased' => 'decimal:2',
            'quantity_used' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    // Calculate closing balance
    public function calculateClosingBalance()
    {
        $this->closing_balance = $this->opening_balance + $this->quantity_purchased - $this->quantity_used;
    }
}

