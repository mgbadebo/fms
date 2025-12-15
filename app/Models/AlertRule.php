<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'sensor_id',
        'rule_type',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function sensor()
    {
        return $this->belongsTo(IoTSensor::class);
    }

    public function alertEvents()
    {
        return $this->hasMany(AlertEvent::class);
    }
}
