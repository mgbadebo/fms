<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'code',
        'name',
        'description',
        'category',
        'requires_quantity',
        'requires_time_range',
        'requires_inputs',
        'requires_photos',
        'schema',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_quantity' => 'boolean',
            'requires_time_range' => 'boolean',
            'requires_inputs' => 'boolean',
            'requires_photos' => 'boolean',
            'is_active' => 'boolean',
            'schema' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function dailyLogItems()
    {
        return $this->hasMany(ProductionCycleDailyLogItem::class);
    }
}
