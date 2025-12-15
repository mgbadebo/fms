<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'title',
        'description',
        'type',
        'related_field_id',
        'related_zone_id',
        'related_crop_plan_id',
        'related_livestock_group_id',
        'due_date',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function relatedField()
    {
        return $this->belongsTo(Field::class, 'related_field_id');
    }

    public function relatedZone()
    {
        return $this->belongsTo(Zone::class, 'related_zone_id');
    }

    public function relatedCropPlan()
    {
        return $this->belongsTo(CropPlan::class, 'related_crop_plan_id');
    }

    public function relatedLivestockGroup()
    {
        return $this->belongsTo(LivestockGroup::class, 'related_livestock_group_id');
    }

    public function assignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function logs()
    {
        return $this->hasMany(TaskLog::class);
    }
}
