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

    // Get all harvests for this greenhouse
    public function harvests()
    {
        return $this->hasManyThrough(BellPepperHarvest::class, BellPepperCycle::class);
    }

    // Calculate total revenue for this greenhouse across all cycles
    public function getTotalRevenue(): float
    {
        $total = 0;
        foreach ($this->bellPepperCycles as $cycle) {
            $total += $cycle->getTotalRevenue();
        }
        return $total;
    }

    // Calculate total revenue for a specific year
    public function getRevenueForYear(int $year): float
    {
        $total = 0;
        foreach ($this->bellPepperCycles()->whereYear('start_date', $year)->get() as $cycle) {
            $total += $cycle->getTotalRevenue();
        }
        return $total;
    }

    // Calculate total expenses for this greenhouse across all cycles
    public function getTotalExpenses(): float
    {
        $total = 0;
        foreach ($this->bellPepperCycles as $cycle) {
            $total += $cycle->getTotalCosts();
        }
        // Add amortized greenhouse and borehole costs
        $total += $this->getAmortizedCostPerCycle() * $this->bellPepperCycles()->count();
        $total += $this->getBoreholeAmortizedCostPerCycle() * $this->bellPepperCycles()->count();
        return $total;
    }

    // Calculate profit margin (revenue - expenses)
    public function getProfitMargin(): float
    {
        return $this->getTotalRevenue() - $this->getTotalExpenses();
    }

    // Calculate profit margin percentage
    public function getProfitMarginPercentage(): float
    {
        $revenue = $this->getTotalRevenue();
        if ($revenue == 0) {
            return 0;
        }
        return (($this->getProfitMargin() / $revenue) * 100);
    }
}
