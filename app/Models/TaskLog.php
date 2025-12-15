<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'worker_id',
        'started_at',
        'ended_at',
        'notes',
        'inputs_used',
        'is_offline_entry',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'inputs_used' => 'array',
            'is_offline_entry' => 'boolean',
        ];
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
