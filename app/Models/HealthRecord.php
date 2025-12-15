<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'recorded_at',
        'type',
        'product_used',
        'dosage',
        'vet_name',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
