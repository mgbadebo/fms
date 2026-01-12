<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCycleDailyLogItemPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'daily_log_item_id',
        'file_path',
        'uploaded_by',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function dailyLogItem()
    {
        return $this->belongsTo(ProductionCycleDailyLogItem::class, 'daily_log_item_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
