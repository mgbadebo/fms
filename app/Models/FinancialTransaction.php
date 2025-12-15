<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'type',
        'category',
        'amount',
        'currency',
        'occurred_at',
        'reference_type',
        'reference_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
