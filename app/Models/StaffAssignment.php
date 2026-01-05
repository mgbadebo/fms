<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'worker_id',
        'assignable_type',
        'assignable_id',
        'role',
        'core_responsibilities',
        'assigned_from',
        'assigned_to',
        'assigned_by',
        'notes',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'assigned_from' => 'date',
            'assigned_to' => 'date',
            'is_current' => 'boolean',
        ];
    }

    // Relationships
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function assignable()
    {
        return $this->morphTo();
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true)
                    ->where(function($q) {
                        $q->whereNull('assigned_to')
                          ->orWhere('assigned_to', '>=', now());
                    });
    }

    public function scopeForWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    // Helper methods
    public function endAssignment($date = null)
    {
        $this->update([
            'assigned_to' => $date ?? now(),
            'is_current' => false,
        ]);
    }
}
