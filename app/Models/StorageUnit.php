<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'inventory_location_id',
        'code',
        'type',
        'capacity_unit',
        'capacity_value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity_value' => 'decimal:2',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function storageContents()
    {
        return $this->hasMany(StorageContent::class);
    }

    public function weighingRecords()
    {
        return $this->morphMany(WeighingRecord::class, 'context');
    }

    public function printedLabels()
    {
        return $this->morphMany(PrintedLabel::class, 'target');
    }
}
