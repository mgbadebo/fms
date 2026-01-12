<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductionCycleHarvestCrate;

class ProductionCycleHarvestCratePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('harvest.view');
    }

    public function view(User $user, ProductionCycleHarvestCrate $crate): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.view')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $crate->farm_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('harvest.create');
    }

    public function update(User $user, ProductionCycleHarvestCrate $crate): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.update')) {
            return false;
        }
        
        if (!$user->farms()->where('farms.id', $crate->farm_id)->exists()) {
            return false;
        }
        
        // Only DRAFT harvest records can have crates updated (unless override permission)
        if ($crate->harvestRecord && $crate->harvestRecord->status !== 'DRAFT' && !$user->can('harvest.override_status')) {
            return false;
        }
        
        return true;
    }

    public function delete(User $user, ProductionCycleHarvestCrate $crate): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.delete')) {
            return false;
        }
        
        if (!$user->farms()->where('farms.id', $crate->farm_id)->exists()) {
            return false;
        }
        
        // Only DRAFT harvest records can have crates deleted (unless override permission)
        if ($crate->harvestRecord && $crate->harvestRecord->status !== 'DRAFT' && !$user->can('harvest.override_status')) {
            return false;
        }
        
        return true;
    }
}
