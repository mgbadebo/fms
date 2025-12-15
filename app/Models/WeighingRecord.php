<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeighingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'scale_device_id',
        'context_type',
        'context_id',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'unit',
        'weighed_at',
        'operator_id',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'gross_weight' => 'decimal:2',
            'tare_weight' => 'decimal:2',
            'net_weight' => 'decimal:2',
            'weighed_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function scaleDevice()
    {
        return $this->belongsTo(ScaleDevice::class);
    }

    public function context()
    {
        return $this->morphTo();
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
