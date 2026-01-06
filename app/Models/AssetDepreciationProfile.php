<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'asset_id',
        'method',
        'useful_life_months',
        'salvage_value',
        'start_date',
    ];

    protected function casts(): array
    {
        return [
            'salvage_value' => 'decimal:2',
            'start_date' => 'date',
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
}
