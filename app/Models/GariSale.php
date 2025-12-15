<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GariSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'sale_code',
        'sale_date',
        'customer_id',
        'customer_name',
        'customer_contact',
        'customer_type',
        'gari_type',
        'gari_grade',
        'packaging_type',
        'quantity_kg',
        'quantity_units',
        'unit_price',
        'total_amount',
        'discount',
        'final_amount',
        'cost_per_kg',
        'total_cost',
        'gross_margin',
        'gross_margin_percent',
        'payment_method',
        'payment_status',
        'amount_paid',
        'amount_outstanding',
        'sales_channel',
        'sales_person',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'quantity_kg' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'cost_per_kg' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'gross_margin' => 'decimal:2',
            'gross_margin_percent' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_outstanding' => 'decimal:2',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Calculate margins
    public function calculateMargins()
    {
        $this->total_cost = $this->cost_per_kg * $this->quantity_kg;
        $this->gross_margin = $this->final_amount - $this->total_cost;
        
        if ($this->final_amount > 0) {
            $this->gross_margin_percent = ($this->gross_margin / $this->final_amount) * 100;
        }
    }

    public function calculatePayment()
    {
        $this->amount_outstanding = $this->final_amount - $this->amount_paid;
        
        if ($this->amount_outstanding <= 0) {
            $this->payment_status = 'PAID';
        } elseif ($this->amount_paid > 0) {
            $this->payment_status = 'PARTIAL';
        } else {
            $this->payment_status = 'OUTSTANDING';
        }
    }
}

