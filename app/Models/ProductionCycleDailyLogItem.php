<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCycleDailyLogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'daily_log_id',
        'activity_type_id',
        'performed_by_user_id',
        'started_at',
        'ended_at',
        'quantity',
        'unit',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'quantity' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function dailyLog()
    {
        return $this->belongsTo(ProductionCycleDailyLog::class, 'daily_log_id');
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function inputs()
    {
        return $this->hasMany(ProductionCycleDailyLogItemInput::class, 'daily_log_item_id');
    }

    public function photos()
    {
        return $this->hasMany(ProductionCycleDailyLogItemPhoto::class, 'daily_log_item_id');
    }
}
