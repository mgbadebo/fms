<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintedLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'label_template_id',
        'target_type',
        'target_id',
        'printed_at',
        'printer_name',
        'payload_sent',
    ];

    protected function casts(): array
    {
        return [
            'printed_at' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function labelTemplate()
    {
        return $this->belongsTo(LabelTemplate::class);
    }

    public function target()
    {
        return $this->morphTo();
    }
}
