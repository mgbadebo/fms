<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScaleDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'connection_type',
        'connection_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'connection_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function weighingRecords()
    {
        return $this->hasMany(WeighingRecord::class);
    }
}
