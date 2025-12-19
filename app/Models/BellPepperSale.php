<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BellPepperSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'bell_pepper_harvest_id',
        'sale_code',
        'sale_date',
        'customer_id',
        'customer_name',
        'customer_contact',
        'customer_type',
        'quantity_kg',
        'crates_count',
        'grade',
        'unit_price',
        'total_amount',
        'discount',
        'final_amount',
        'logistics_cost',
        'payment_method',
        'payment_status',
        'amount_paid',
        'amount_outstanding',
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
            'logistics_cost' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_outstanding' => 'decimal:2',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function harvest()
    {
        return $this->belongsTo(BellPepperHarvest::class, 'bell_pepper_harvest_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Calculate payment status
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
