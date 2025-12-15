<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoutingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'crop_plan_id',
        'field_id',
        'zone_id',
        'observed_at',
        'issue_type',
        'description',
        'severity',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function cropPlan()
    {
        return $this->belongsTo(CropPlan::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
