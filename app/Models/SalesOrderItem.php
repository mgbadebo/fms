<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'sales_order_id',
        'product_id',
        'production_cycle_id',
        'harvest_record_id',
        'harvest_lot_id',
        'product_name',
        'product_description',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'line_total',
        'quality_grade',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Derive farm_id from sales_order
            if (!$item->farm_id && $item->sales_order_id) {
                $order = \App\Models\SalesOrder::find($item->sales_order_id);
                if ($order) {
                    $item->farm_id = $order->farm_id;
                }
            }
            // Calculate line_total
            if (!$item->line_total) {
                $item->line_total = ($item->quantity * $item->unit_price) - ($item->discount_amount ?? 0);
            }
        });

        static::updating(function ($item) {
            // Recalculate line_total
            $item->line_total = ($item->quantity * $item->unit_price) - ($item->discount_amount ?? 0);
        });

        static::saved(function ($item) {
            // Recalculate order totals
            if ($item->salesOrder) {
                $item->salesOrder->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            // Recalculate order totals
            if ($item->salesOrder) {
                $item->salesOrder->recalculateTotals();
            }
        });
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productionCycle()
    {
        return $this->belongsTo(GreenhouseProductionCycle::class, 'production_cycle_id');
    }

    public function harvestRecord()
    {
        // Polymorphic-like: try new production cycle harvest record first, fallback to legacy
        if ($this->harvest_record_id) {
            $record = \App\Models\ProductionCycleHarvestRecord::find($this->harvest_record_id);
            if ($record) {
                return $record;
            }
            return \App\Models\BellPepperHarvest::find($this->harvest_record_id);
        }
        return null;
    }
    
    public function bellPepperHarvest()
    {
        return $this->belongsTo(BellPepperHarvest::class, 'harvest_record_id');
    }
    
    public function productionCycleHarvestRecord()
    {
        return $this->belongsTo(ProductionCycleHarvestRecord::class, 'harvest_record_id');
    }

    public function harvestLot()
    {
        return $this->belongsTo(HarvestLot::class);
    }
}
