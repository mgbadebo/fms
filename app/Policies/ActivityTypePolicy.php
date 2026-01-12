<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ActivityType;

class ActivityTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('activity_types.view');
    }

    public function view(User $user, ActivityType $activityType): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('activity_types.view')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $activityType->farm_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('activity_types.manage');
    }

    public function update(User $user, ActivityType $activityType): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('activity_types.manage')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $activityType->farm_id)->exists();
    }

    public function delete(User $user, ActivityType $activityType): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('activity_types.manage')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $activityType->farm_id)->exists();
    }
}
