<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenancePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'asset_id',
        'plan_type',
        'interval_value',
        'last_service_at',
        'next_due_at',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_service_at' => 'datetime',
            'next_due_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
