<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCycleAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'production_cycle_id',
        'log_date',
        'alert_type',
        'message',
        'severity',
        'is_resolved',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'resolved_at' => 'datetime',
            'is_resolved' => 'boolean',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function productionCycle()
    {
        return $this->belongsTo(GreenhouseProductionCycle::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
