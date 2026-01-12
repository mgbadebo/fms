<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductionCycleHarvestRecord;

class ProductionCycleHarvestRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('harvest.view');
    }

    public function view(User $user, ProductionCycleHarvestRecord $harvestRecord): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.view')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $harvestRecord->farm_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('harvest.create');
    }

    public function update(User $user, ProductionCycleHarvestRecord $harvestRecord): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.update')) {
            return false;
        }
        
        if (!$user->farms()->where('farms.id', $harvestRecord->farm_id)->exists()) {
            return false;
        }
        
        // Only DRAFT records can be updated (unless override permission)
        if ($harvestRecord->status !== 'DRAFT' && !$user->can('harvest.override_status')) {
            return false;
        }
        
        return true;
    }

    public function delete(User $user, ProductionCycleHarvestRecord $harvestRecord): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('harvest.delete')) {
            return false;
        }
        
        if (!$user->farms()->where('farms.id', $harvestRecord->farm_id)->exists()) {
            return false;
        }
        
        // Only DRAFT records can be deleted
        return $harvestRecord->status === 'DRAFT';
    }
}
