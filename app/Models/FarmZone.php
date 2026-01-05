<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'crop_id',
        'name',
        'code',
        'description',
        'area',
        'area_unit',
        'produce_type',
        'geometry',
        'soil_type',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'area' => 'decimal:2',
        ];
    }

    // Relationships
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    // Staff assignments to this zone
    public function staffAssignments()
    {
        return $this->morphMany(StaffAssignment::class, 'assignable');
    }
}
