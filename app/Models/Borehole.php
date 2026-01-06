<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Borehole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'code',
        'name',
        'installed_date',
        'installation_cost',
        'amortization_cycles',
        'site_id',
        'specifications',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'installed_date' => 'date',
            'installation_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function greenhouses()
    {
        return $this->belongsToMany(Greenhouse::class, 'greenhouse_borehole');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // Calculate amortized cost per cycle
    public function getAmortizedCostPerCycle(): float
    {
        if ($this->amortization_cycles <= 0) {
            return 0;
        }
        return (float)($this->installation_cost / $this->amortization_cycles);
    }
}
