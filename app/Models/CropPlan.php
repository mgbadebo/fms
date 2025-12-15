<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'field_id',
        'zone_id',
        'season_id',
        'crop_id',
        'planting_date',
        'target_yield',
        'yield_unit',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planting_date' => 'date',
            'target_yield' => 'decimal:2',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function scoutingLogs()
    {
        return $this->hasMany(ScoutingLog::class);
    }

    public function harvestLots()
    {
        return $this->hasMany(HarvestLot::class);
    }

    public function inputApplications()
    {
        return $this->hasMany(InputApplication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'related_crop_plan_id');
    }
}
