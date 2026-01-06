<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'code',
        'type',
        'description',
        'address',
        'latitude',
        'longitude',
        'total_area',
        'area_unit',
        'metadata',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'total_area' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    // Relationships
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }


    // Site type specific relationships
    public function farmZones()
    {
        return $this->hasMany(FarmZone::class);
    }

    public function greenhouses()
    {
        return $this->hasMany(Greenhouse::class);
    }

    public function factories()
    {
        return $this->hasMany(Factory::class);
    }

    // Staff assignments to this site
    public function staffAssignments()
    {
        return $this->morphMany(StaffAssignment::class, 'assignable');
    }

    // Scope for filtering by type
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFarmlands($query)
    {
        return $query->where('type', 'farmland');
    }

    public function scopeWarehouses($query)
    {
        return $query->where('type', 'warehouse');
    }

    public function scopeFactories($query)
    {
        return $query->where('type', 'factory');
    }

    public function scopeGreenhouses($query)
    {
        return $query->where('type', 'greenhouse');
    }
}
