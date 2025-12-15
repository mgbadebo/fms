<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'livestock_group_id',
        'tag_id',
        'sex',
        'birth_date',
        'status',
        'lineage_info',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'lineage_info' => 'array',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function livestockGroup()
    {
        return $this->belongsTo(LivestockGroup::class);
    }

    public function breedingEvents()
    {
        return $this->hasMany(BreedingEvent::class);
    }

    public function healthRecords()
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function feedRecords()
    {
        return $this->hasMany(FeedRecord::class);
    }
}
