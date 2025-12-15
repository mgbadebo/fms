<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'sensor_id',
        'recorded_at',
        'value',
        'unit',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'value' => 'decimal:4',
            'extra' => 'array',
        ];
    }

    public function sensor()
    {
        return $this->belongsTo(IoTSensor::class, 'sensor_id');
    }
}
