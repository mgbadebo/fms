<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IoTSensor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'type',
        'external_id',
        'location_field_id',
        'location_zone_id',
        'meta',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function locationField()
    {
        return $this->belongsTo(Field::class, 'location_field_id');
    }

    public function locationZone()
    {
        return $this->belongsTo(Zone::class, 'location_zone_id');
    }

    public function readings()
    {
        return $this->hasMany(SensorReading::class, 'sensor_id');
    }

    public function alertRules()
    {
        return $this->hasMany(AlertRule::class);
    }
}
