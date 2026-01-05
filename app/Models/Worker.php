<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'user_id',
        'name',
        'contact',
        'type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function taskLogs()
    {
        return $this->hasMany(TaskLog::class);
    }

    // Staff assignments (can be assigned to Sites, Factories, Greenhouses, FarmZones)
    public function staffAssignments()
    {
        return $this->hasMany(StaffAssignment::class);
    }

    // Current assignments only
    public function currentAssignments()
    {
        return $this->hasMany(StaffAssignment::class)->where('is_current', true)
            ->where(function($q) {
                $q->whereNull('assigned_to')
                  ->orWhere('assigned_to', '>=', now());
            });
    }
}
