<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'type',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(InventoryStockMovement::class);
    }

    public function storageUnits()
    {
        return $this->hasMany(StorageUnit::class);
    }

    public function harvestLots()
    {
        return $this->hasMany(HarvestLot::class, 'storage_location_id');
    }
}
