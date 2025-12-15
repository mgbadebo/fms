<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'alert_rule_id',
        'triggered_at',
        'message',
        'severity',
        'is_resolved',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
            'is_resolved' => 'boolean',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function alertRule()
    {
        return $this->belongsTo(AlertRule::class);
    }
}
