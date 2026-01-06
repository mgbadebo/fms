<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetInsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'asset_id',
        'insurer_name',
        'policy_number',
        'coverage_start',
        'coverage_end',
        'insured_value',
        'currency',
        'premium',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'coverage_start' => 'date',
            'coverage_end' => 'date',
            'insured_value' => 'decimal:2',
            'premium' => 'decimal:2',
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
        return $query->where('coverage_end', '>=', now());
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->whereBetween('coverage_end', [now(), now()->addDays($days)]);
    }
}
