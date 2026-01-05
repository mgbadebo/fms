<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BellPepperHarvest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'bell_pepper_cycle_id',
        'greenhouse_id',
        'harvester_id',
        'harvest_code',
        'harvest_date',
        'harvest_number',
        'weight_kg',
        'grade_a_kg',
        'grade_b_kg',
        'grade_c_kg',
        'crates_count',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'weight_kg' => 'decimal:2',
            'grade_a_kg' => 'decimal:2',
            'grade_b_kg' => 'decimal:2',
            'grade_c_kg' => 'decimal:2',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function cycle()
    {
        return $this->belongsTo(BellPepperCycle::class, 'bell_pepper_cycle_id');
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function harvester()
    {
        return $this->belongsTo(User::class, 'harvester_id');
    }

    public function sales()
    {
        return $this->hasMany(BellPepperSale::class);
    }

    // Calculate total weight from grades
    public function calculateTotalWeight(): float
    {
        return (float)($this->grade_a_kg ?? 0) + (float)($this->grade_b_kg ?? 0) + (float)($this->grade_c_kg ?? 0);
    }

    // Update weight_kg from grades (call this before saving)
    public function updateTotalWeight(): void
    {
        $this->weight_kg = $this->calculateTotalWeight();
    }

    // Calculate remaining weight per grade (not yet sold)
    public function getRemainingWeightByGrade(string $grade): float
    {
        $gradeField = 'grade_' . strtolower($grade) . '_kg';
        $harvestedWeight = (float)($this->$gradeField ?? 0);
        $soldWeight = (float)$this->sales()->where('grade', $grade)->sum('quantity_kg');
        return max(0, $harvestedWeight - $soldWeight);
    }

    // Calculate remaining weight (not yet sold) - total across all grades
    public function getRemainingWeight(): float
    {
        $totalHarvested = $this->calculateTotalWeight();
        $soldWeight = (float)$this->sales()->sum('quantity_kg');
        return max(0, $totalHarvested - $soldWeight);
    }

    // Calculate revenue from sales for this harvest
    public function getRevenue(): float
    {
        return (float)$this->sales()->sum('final_amount');
    }

    // Calculate revenue by grade for this harvest
    public function getRevenueByGrade(string $grade): float
    {
        return (float)$this->sales()->where('grade', $grade)->sum('final_amount');
    }

    // Boot method to auto-calculate weight_kg before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($harvest) {
            $harvest->updateTotalWeight();
        });
    }
}
