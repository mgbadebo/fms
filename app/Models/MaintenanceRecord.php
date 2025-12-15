<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'asset_id',
        'performed_at',
        'type',
        'cost',
        'currency',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'cost' => 'decimal:2',
            'metadata' => 'array',
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
