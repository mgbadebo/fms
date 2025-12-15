<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'field_id',
        'name',
        'relative_area_percent',
        'geometry',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'relative_area_percent' => 'decimal:2',
        ];
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
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
        return $this->hasMany(Task::class, 'related_zone_id');
    }

    public function iotSensors()
    {
        return $this->hasMany(IoTSensor::class, 'location_zone_id');
    }
}
