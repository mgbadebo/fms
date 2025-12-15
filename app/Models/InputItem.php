<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InputItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'category',
        'unit',
        'default_cost',
        'currency',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'default_cost' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(InventoryStockMovement::class);
    }

    public function applications()
    {
        return $this->hasMany(InputApplication::class);
    }
}
