<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'category',
        'serial_number',
        'purchase_date',
        'status',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }
}
