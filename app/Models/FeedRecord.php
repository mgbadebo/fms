<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'livestock_group_id',
        'animal_id',
        'recorded_at',
        'feed_item',
        'quantity',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'quantity' => 'decimal:2',
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
