<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    /**
     * Determine if the user can view any assets.
     */
    public function viewAny(User $user): bool
    {
        // Any authenticated user can view assets (scoped by farm)
        return true;
    }

    /**
     * Determine if the user can view the asset.
     */
    public function view(User $user, Asset $asset): bool
    {
        // User can view if they belong to the same farm
        return $user->farms()->where('farms.id', $asset->farm_id)->exists();
    }

    /**
     * Determine if the user can create assets.
     */
    public function create(User $user): bool
    {
        // Only MANAGER or ADMIN can create
        return $user->hasAnyRole(['MANAGER', 'ADMIN']);
    }

    /**
     * Determine if the user can update the asset.
     */
    public function update(User $user, Asset $asset): bool
    {
        // User must belong to the farm and be MANAGER or ADMIN
        $belongsToFarm = $user->farms()->where('farms.id', $asset->farm_id)->exists();
        return $belongsToFarm && $user->hasAnyRole(['MANAGER', 'ADMIN']);
    }

    /**
     * Determine if the user can delete the asset.
     */
    public function delete(User $user, Asset $asset): bool
    {
        // Only ADMIN can delete/dispose
        $belongsToFarm = $user->farms()->where('farms.id', $asset->farm_id)->exists();
        return $belongsToFarm && $user->hasRole('ADMIN');
    }

    /**
     * Determine if the user can assign assets.
     */
    public function assign(User $user, Asset $asset): bool
    {
        $belongsToFarm = $user->farms()->where('farms.id', $asset->farm_id)->exists();
        return $belongsToFarm && $user->hasAnyRole(['MANAGER', 'ADMIN']);
    }

    /**
     * Determine if the user can maintain assets.
     */
    public function maintain(User $user, Asset $asset): bool
    {
        $belongsToFarm = $user->farms()->where('farms.id', $asset->farm_id)->exists();
        return $belongsToFarm && $user->hasAnyRole(['MANAGER', 'ADMIN', 'WORKER']);
    }
}
