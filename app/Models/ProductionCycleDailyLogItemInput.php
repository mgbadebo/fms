<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCycleDailyLogItemInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'daily_log_item_id',
        'input_item_id',
        'input_name',
        'quantity',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function dailyLogItem()
    {
        return $this->belongsTo(ProductionCycleDailyLogItem::class, 'daily_log_item_id');
    }

    public function inputItem()
    {
        return $this->belongsTo(InputItem::class);
    }
}
