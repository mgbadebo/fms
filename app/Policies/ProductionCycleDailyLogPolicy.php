<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductionCycleDailyLog;

class ProductionCycleDailyLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('daily_logs.view');
    }

    public function view(User $user, ProductionCycleDailyLog $log): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('daily_logs.view')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $log->farm_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('daily_logs.create');
    }

    public function update(User $user, ProductionCycleDailyLog $log): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('daily_logs.update')) {
            return false;
        }
        
        // Can only update DRAFT logs
        if ($log->status !== 'DRAFT') {
            return false;
        }
        
        return $user->farms()->where('farms.id', $log->farm_id)->exists();
    }

    public function delete(User $user, ProductionCycleDailyLog $log): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('daily_logs.delete')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $log->farm_id)->exists();
    }
}
