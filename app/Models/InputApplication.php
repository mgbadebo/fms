<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InputApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'input_item_id',
        'field_id',
        'zone_id',
        'crop_plan_id',
        'livestock_group_id',
        'applied_at',
        'quantity',
        'unit',
        'method',
        'operator_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'quantity' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function inputItem()
    {
        return $this->belongsTo(InputItem::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function cropPlan()
    {
        return $this->belongsTo(CropPlan::class);
    }

    public function livestockGroup()
    {
        return $this->belongsTo(LivestockGroup::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
