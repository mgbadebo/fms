<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Greenhouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'code',
        'name',
        'size_sqm',
        'built_date',
        'construction_cost',
        'amortization_cycles',
        'location_id',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'built_date' => 'date',
            'size_sqm' => 'decimal:2',
            'construction_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function bellPepperCycles()
    {
        return $this->hasMany(BellPepperCycle::class);
    }

    public function boreholes()
    {
        return $this->belongsToMany(Borehole::class, 'greenhouse_borehole');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // Calculate amortized cost per cycle
    public function getAmortizedCostPerCycle(): float
    {
        if ($this->amortization_cycles <= 0) {
            return 0;
        }
        return (float)($this->construction_cost / $this->amortization_cycles);
    }

    // Calculate total borehole amortized cost per cycle (sum of all linked boreholes)
    public function getBoreholeAmortizedCostPerCycle(): float
    {
        $total = 0;
        foreach ($this->boreholes as $borehole) {
            $total += $borehole->getAmortizedCostPerCycle();
        }
        return $total;
    }
}
