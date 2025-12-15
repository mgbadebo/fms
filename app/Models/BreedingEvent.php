<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreedingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'livestock_group_id',
        'animal_id',
        'event_date',
        'type',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
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

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
