<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('products.view')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $product->farm_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('products.manage');
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('products.manage')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $product->farm_id)->exists();
    }

    public function delete(User $user, Product $product): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('products.manage')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $product->farm_id)->exists();
    }
}
