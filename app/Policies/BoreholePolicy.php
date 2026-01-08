<?php

namespace App\Policies;

use App\Models\Borehole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoreholePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('ADMIN')) return true;
        return $user->can('boreholes.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Borehole $borehole): bool
    {
        if ($user->hasRole('ADMIN')) return true;
        return $user->can('boreholes.view') && $user->farms()->where('farms.id', $borehole->farm_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('ADMIN')) return true;
        return $user->can('boreholes.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Borehole $borehole): bool
    {
        if ($user->hasRole('ADMIN')) return true;
        return $user->can('boreholes.update') && $user->farms()->where('farms.id', $borehole->farm_id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Borehole $borehole): bool
    {
        return $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Borehole $borehole): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Borehole $borehole): bool
    {
        return false;
    }
}
