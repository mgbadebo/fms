<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LivestockBreed extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'species',
        'name',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function livestockGroups()
    {
        return $this->hasMany(LivestockGroup::class, 'breed_id');
    }
}
