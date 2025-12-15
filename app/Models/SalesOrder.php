<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'customer_id',
        'order_number',
        'order_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salesOrder) {
            if (empty($salesOrder->order_number)) {
                $salesOrder->order_number = 'SO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
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

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
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
