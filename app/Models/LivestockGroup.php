<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LivestockGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'species',
        'breed_id',
        'start_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function breed()
    {
        return $this->belongsTo(LivestockBreed::class, 'breed_id');
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function breedingEvents()
    {
        return $this->hasMany(BreedingEvent::class);
    }

    public function feedRecords()
    {
        return $this->hasMany(FeedRecord::class);
    }

    public function inputApplications()
    {
        return $this->hasMany(InputApplication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'related_livestock_group_id');
    }
}
