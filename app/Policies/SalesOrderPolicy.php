<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SalesOrder;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('sales_orders.view');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('sales_orders.view')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $salesOrder->farm_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('sales_orders.create');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('sales_orders.update')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $salesOrder->farm_id)->exists();
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        if (!$user->can('sales_orders.delete')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $salesOrder->farm_id)->exists();
    }
}
