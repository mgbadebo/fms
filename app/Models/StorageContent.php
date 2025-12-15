<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'storage_unit_id',
        'harvest_lot_id',
        'quantity',
        'unit',
        'stored_at',
        'removed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'stored_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function storageUnit()
    {
        return $this->belongsTo(StorageUnit::class);
    }

    public function harvestLot()
    {
        return $this->belongsTo(HarvestLot::class);
    }
}
