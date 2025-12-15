<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GariProductionBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'batch_code',
        'processing_date',
        'cassava_source',
        'cassava_quantity_kg',
        'cassava_quantity_tonnes',
        'cassava_cost_per_kg',
        'cassava_cost_per_tonne',
        'total_cassava_cost',
        'gari_produced_kg',
        'gari_type',
        'gari_grade',
        'conversion_yield_percent',
        'labour_cost',
        'fuel_cost',
        'equipment_cost',
        'water_cost',
        'transport_cost',
        'other_costs',
        'total_processing_cost',
        'waste_kg',
        'waste_percent',
        'total_cost',
        'cost_per_kg_gari',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'processing_date' => 'date',
            'cassava_quantity_kg' => 'decimal:2',
            'cassava_quantity_tonnes' => 'decimal:3',
            'cassava_cost_per_kg' => 'decimal:2',
            'cassava_cost_per_tonne' => 'decimal:2',
            'total_cassava_cost' => 'decimal:2',
            'gari_produced_kg' => 'decimal:2',
            'conversion_yield_percent' => 'decimal:2',
            'labour_cost' => 'decimal:2',
            'fuel_cost' => 'decimal:2',
            'equipment_cost' => 'decimal:2',
            'water_cost' => 'decimal:2',
            'transport_cost' => 'decimal:2',
            'other_costs' => 'decimal:2',
            'total_processing_cost' => 'decimal:2',
            'waste_kg' => 'decimal:2',
            'waste_percent' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'cost_per_kg_gari' => 'decimal:2',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function cassavaInputs()
    {
        return $this->hasMany(CassavaInput::class);
    }

    public function gariInventory()
    {
        return $this->hasMany(GariInventory::class);
    }

    public function wasteLosses()
    {
        return $this->hasMany(GariWasteLoss::class);
    }

    // Helper methods for unit conversion
    public function getCassavaQuantityInKg()
    {
        // If tonnes is set, convert to kg (1 tonne = 1000 kg)
        if ($this->cassava_quantity_tonnes) {
            return $this->cassava_quantity_tonnes * 1000;
        }
        // Fallback to kg if tonnes not set
        return $this->cassava_quantity_kg ?? 0;
    }

    // Calculated attributes
    public function calculateYield()
    {
        $cassavaKg = $this->getCassavaQuantityInKg();
        if ($cassavaKg > 0 && $this->gari_produced_kg > 0) {
            $this->conversion_yield_percent = ($this->gari_produced_kg / $cassavaKg) * 100;
        }
    }

    public function calculateCosts()
    {
        $this->total_processing_cost = $this->labour_cost + $this->fuel_cost + $this->equipment_cost 
            + $this->water_cost + $this->transport_cost + $this->other_costs;
        
        $this->total_cost = ($this->total_cassava_cost ?? 0) + $this->total_processing_cost;
        
        if ($this->gari_produced_kg > 0) {
            $this->cost_per_kg_gari = $this->total_cost / $this->gari_produced_kg;
        }
    }

    public function calculateWaste()
    {
        $cassavaKg = $this->getCassavaQuantityInKg();
        if ($cassavaKg > 0 && $this->waste_kg > 0) {
            $this->waste_percent = ($this->waste_kg / $cassavaKg) * 100;
        }
    }
}

