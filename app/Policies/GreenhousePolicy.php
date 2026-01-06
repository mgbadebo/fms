<?php

namespace App\Policies;

use App\Models\Greenhouse;
use App\Models\User;

class GreenhousePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin has all permissions
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        // Check if user has greenhouses.view permission
        return $user->can('greenhouses.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Greenhouse $greenhouse): bool
    {
        // Admin has all permissions
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        // Check permission and farm membership
        if (!$user->can('greenhouses.view')) {
            return false;
        }
        
        // User must belong to the farm that owns this greenhouse
        return $user->farms()->where('farms.id', $greenhouse->farm_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin has all permissions
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        // Check if user has greenhouses.create permission
        return $user->can('greenhouses.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Greenhouse $greenhouse): bool
    {
        // Admin has all permissions
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        // Check permission and farm membership
        if (!$user->can('greenhouses.update')) {
            return false;
        }
        
        // User must belong to the farm that owns this greenhouse
        return $user->farms()->where('farms.id', $greenhouse->farm_id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Greenhouse $greenhouse): bool
    {
        // Only ADMIN can delete
        return $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Greenhouse $greenhouse): bool
    {
        // Only ADMIN can restore
        return $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Greenhouse $greenhouse): bool
    {
        // Only ADMIN can force delete
        return $user->hasRole('ADMIN');
    }
}
