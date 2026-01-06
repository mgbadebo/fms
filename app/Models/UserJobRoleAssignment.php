<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserJobRoleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'user_id',
        'worker_job_role_id',
        'assigned_at',
        'ended_at',
        'assigned_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the farm for this assignment.
     */
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Get the user for this assignment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the worker job role for this assignment.
     */
    public function workerJobRole()
    {
        return $this->belongsTo(WorkerJobRole::class);
    }

    /**
     * Get the user who assigned this role.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * Check if this assignment is active.
     */
    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    /**
     * Scope to get only active assignments.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }
}
