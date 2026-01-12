<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('customers.manage');
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        return $user->can('customers.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->can('customers.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        return $user->can('customers.manage');
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        return $user->can('customers.manage');
    }
}
