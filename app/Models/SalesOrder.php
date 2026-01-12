<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'site_id',
        'customer_id',
        'order_number',
        'order_date',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'total_amount',
        'payment_status',
        'due_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salesOrder) {
            if (empty($salesOrder->order_number)) {
                $salesOrder->order_number = 'SO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
            if (empty($salesOrder->currency) && $salesOrder->farm_id) {
                $farm = \App\Models\Farm::find($salesOrder->farm_id);
                $salesOrder->currency = $farm->default_currency ?? 'USD';
            }
            if (empty($salesOrder->created_by) && auth()->check()) {
                $salesOrder->created_by = auth()->id();
            }
        });
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Recalculate totals from items.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float)$this->items()->sum('line_total');
        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal - ($this->discount_total ?? 0) + ($this->tax_total ?? 0);
        $this->save();
    }

    /**
     * Refresh payment status based on payments.
     */
    public function refreshPaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        
        if ($totalPaid >= $this->total_amount) {
            $this->payment_status = 'PAID';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'PART_PAID';
        } else {
            $this->payment_status = 'UNPAID';
        }
        
        $this->save();
    }

    public function weighingRecords()
    {
        return $this->morphMany(WeighingRecord::class, 'context');
    }

    public function printedLabels()
    {
        return $this->morphMany(PrintedLabel::class, 'target');
    }
}
