<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerJobRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }


    /**
     * Get all user assignments for this job role.
     */
    public function userAssignments()
    {
        return $this->hasMany(UserJobRoleAssignment::class);
    }

    /**
     * Get active user assignments for this job role.
     */
    public function activeAssignments()
    {
        return $this->hasMany(UserJobRoleAssignment::class)->whereNull('ended_at');
    }
}
