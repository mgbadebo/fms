<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BellPepperCycle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'greenhouse_id',
        'cycle_code',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'status',
        'expected_yield_kg',
        'expected_yield_per_sqm',
        'actual_yield_kg',
        'actual_yield_per_sqm',
        'yield_variance_percent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'actual_end_date' => 'date',
            'expected_yield_kg' => 'decimal:2',
            'expected_yield_per_sqm' => 'decimal:2',
            'actual_yield_kg' => 'decimal:2',
            'actual_yield_per_sqm' => 'decimal:2',
            'yield_variance_percent' => 'decimal:2',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function costs()
    {
        return $this->hasMany(BellPepperCycleCost::class);
    }

    public function harvests()
    {
        return $this->hasMany(BellPepperHarvest::class);
    }

    // Calculate total costs for this cycle
    public function getTotalCosts(): float
    {
        return (float)$this->costs()->sum('total_cost');
    }

    // Calculate yield variance
    public function calculateYieldVariance()
    {
        if ($this->expected_yield_kg > 0 && $this->actual_yield_kg > 0) {
            $variance = (($this->actual_yield_kg - $this->expected_yield_kg) / $this->expected_yield_kg) * 100;
            $this->yield_variance_percent = round($variance, 2);
        } else {
            $this->yield_variance_percent = 0;
        }
    }

    // Calculate actual yield per sqm
    public function calculateActualYieldPerSqm()
    {
        if ($this->greenhouse && $this->greenhouse->size_sqm > 0 && $this->actual_yield_kg > 0) {
            $this->actual_yield_per_sqm = round($this->actual_yield_kg / $this->greenhouse->size_sqm, 2);
        } else {
            $this->actual_yield_per_sqm = 0;
        }
    }

    // Calculate total revenue from all sales for this cycle
    public function getTotalRevenue(): float
    {
        $total = 0;
        foreach ($this->harvests as $harvest) {
            $total += $harvest->getRevenue();
        }
        return $total;
    }

    // Calculate revenue by grade for this cycle
    public function getRevenueByGrade(string $grade): float
    {
        $total = 0;
        foreach ($this->harvests as $harvest) {
            $total += $harvest->getRevenueByGrade($grade);
        }
        return $total;
    }

    // Calculate profit margin (revenue - expenses) for this cycle
    public function getProfitMargin(): float
    {
        $revenue = $this->getTotalRevenue();
        $expenses = $this->getTotalCosts();
        // Add amortized greenhouse and borehole costs
        if ($this->greenhouse) {
            $expenses += $this->greenhouse->getAmortizedCostPerCycle();
            $expenses += $this->greenhouse->getBoreholeAmortizedCostPerCycle();
        }
        return $revenue - $expenses;
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
