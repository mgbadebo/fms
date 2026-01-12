<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'sales_order_id',
        'payment_date',
        'amount',
        'currency',
        'method',
        'reference',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($payment) {
            // Refresh payment status on the sales order
            if ($payment->salesOrder) {
                $payment->salesOrder->refreshPaymentStatus();
            }
        });

        static::deleted(function ($payment) {
            // Refresh payment status on the sales order
            if ($payment->salesOrder) {
                $payment->salesOrder->refreshPaymentStatus();
            }
        });
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
