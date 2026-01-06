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
        'vendor_name',
        'cost',
        'currency',
        'odometer_or_hours',
        'description',
        'parts_used',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'cost' => 'decimal:2',
            'odometer_or_hours' => 'decimal:2',
            'parts_used' => 'array',
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
