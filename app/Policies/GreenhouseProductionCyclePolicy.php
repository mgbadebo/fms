<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GreenhouseProductionCycle;

class GreenhouseProductionCyclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('production_cycles.view');
    }

    public function view(User $user, GreenhouseProductionCycle $cycle): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('production_cycles.view')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $cycle->farm_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('production_cycles.create');
    }

    public function update(User $user, GreenhouseProductionCycle $cycle): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('production_cycles.update')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $cycle->farm_id)->exists();
    }

    public function delete(User $user, GreenhouseProductionCycle $cycle): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('production_cycles.delete')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $cycle->farm_id)->exists();
    }
}
