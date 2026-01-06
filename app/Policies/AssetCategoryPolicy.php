<?php

namespace App\Policies;

use App\Models\AssetCategory;
use App\Models\User;

class AssetCategoryPolicy
{
    /**
     * Determine if the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the category.
     */
    public function view(User $user, AssetCategory $category): bool
    {
        return $user->farms()->where('farms.id', $category->farm_id)->exists();
    }

    /**
     * Determine if the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['MANAGER', 'ADMIN']);
    }

    /**
     * Determine if the user can update the category.
     */
    public function update(User $user, AssetCategory $category): bool
    {
        $belongsToFarm = $user->farms()->where('farms.id', $category->farm_id)->exists();
        return $belongsToFarm && $user->hasAnyRole(['MANAGER', 'ADMIN']);
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User $user, AssetCategory $category): bool
    {
        $belongsToFarm = $user->farms()->where('farms.id', $category->farm_id)->exists();
        return $belongsToFarm && $user->hasRole('ADMIN');
    }
}
