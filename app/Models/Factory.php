<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'asset_id', // Link to Asset if tracked as asset
        'name',
        'code',
        'production_type',
        'description',
        'area_sqm',
        'established_date',
        'equipment',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'equipment' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'area_sqm' => 'decimal:2',
            'established_date' => 'date',
        ];
    }

    // Relationships
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    // Staff assignments to this factory
    public function staffAssignments()
    {
        return $this->morphMany(StaffAssignment::class, 'assignable');
    }

    // Production batches (for Gari factories)
    public function gariProductionBatches()
    {
        return $this->hasMany(GariProductionBatch::class);
    }

    // Scope for production type
    public function scopeGari($query)
    {
        return $query->where('production_type', 'gari');
    }
}
