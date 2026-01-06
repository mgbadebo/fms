<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'admin_zones';

    protected $fillable = [
        'site_id',
        'code',
        'name',
        'description',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function farms()
    {
        return $this->hasMany(Farm::class, 'admin_zone_id');
    }
}
