<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'asset_id',
        'filled_at',
        'quantity',
        'unit',
        'cost',
        'currency',
        'operator_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'filled_at' => 'datetime',
            'quantity' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
