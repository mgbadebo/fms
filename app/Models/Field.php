<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'geometry_reference',
        'geometry',
        'area',
        'area_unit',
        'soil_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'area' => 'decimal:2',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function cropPlans()
    {
        return $this->hasMany(CropPlan::class);
    }

    public function scoutingLogs()
    {
        return $this->hasMany(ScoutingLog::class);
    }

    public function harvestLots()
    {
        return $this->hasMany(HarvestLot::class);
    }

    public function inputApplications()
    {
        return $this->hasMany(InputApplication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'related_field_id');
    }

    public function iotSensors()
    {
        return $this->hasMany(IoTSensor::class, 'location_field_id');
    }
}
