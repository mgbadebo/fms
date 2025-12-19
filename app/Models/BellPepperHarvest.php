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
        'harvest_code',
        'harvest_date',
        'weight_kg',
        'crates_count',
        'grade',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'weight_kg' => 'decimal:2',
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

    public function sales()
    {
        return $this->hasMany(BellPepperSale::class);
    }

    // Calculate remaining weight (not yet sold)
    public function getRemainingWeight(): float
    {
        $soldWeight = (float)$this->sales()->sum('quantity_kg');
        return max(0, (float)$this->weight_kg - $soldWeight);
    }
}
