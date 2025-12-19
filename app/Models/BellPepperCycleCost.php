<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BellPepperCycleCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'bell_pepper_cycle_id',
        'farm_id',
        'cost_type',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'cost_date',
        'staff_id',
        'hours_allocated',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'cost_date' => 'date',
            'hours_allocated' => 'decimal:2',
        ];
    }

    // Relationships
    public function cycle()
    {
        return $this->belongsTo(BellPepperCycle::class, 'bell_pepper_cycle_id');
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
