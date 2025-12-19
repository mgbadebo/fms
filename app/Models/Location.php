<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function zones()
    {
        return $this->hasMany(AdminZone::class);
    }

    public function farms()
    {
        return $this->hasMany(Farm::class);
    }

    public function greenhouses()
    {
        return $this->hasMany(Greenhouse::class);
    }

    public function boreholes()
    {
        return $this->hasMany(Borehole::class);
    }
}
