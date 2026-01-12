<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCycleDailyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'site_id',
        'greenhouse_id',
        'production_cycle_id',
        'log_date',
        'status',
        'submitted_at',
        'submitted_by',
        'issues_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function productionCycle()
    {
        return $this->belongsTo(GreenhouseProductionCycle::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ProductionCycleDailyLogItem::class, 'daily_log_id');
    }
}
