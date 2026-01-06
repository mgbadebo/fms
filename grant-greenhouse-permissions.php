<?php

/**
 * Script to grant Greenhouse Management permissions to ADMIN users
 * 
 * Usage: php grant-greenhouse-permissions.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "Granting Greenhouse Management permissions to ADMIN users...\n\n";

// Get or create permissions
$permissions = [
    'admin.greenhouses.view',
    'admin.greenhouses.create',
    'admin.greenhouses.update',
];

$createdPermissions = [];
foreach ($permissions as $permissionName) {
    $permission = Permission::firstOrCreate(
        ['name' => $permissionName, 'guard_name' => 'web'],
        ['name' => $permissionName, 'guard_name' => 'web']
    );
    $createdPermissions[] = $permission;
    echo "✓ Permission '{$permissionName}' ensured\n";
}

// Get ADMIN role
$adminRole = Role::where('name', 'ADMIN')->where('guard_name', 'web')->first();

if (!$adminRole) {
    echo "\n❌ ERROR: ADMIN role not found. Please create it first.\n";
    exit(1);
}

echo "\n✓ ADMIN role found\n";

// Grant all permissions to ADMIN role
$adminRole->syncPermissions(Permission::all());
echo "✓ All permissions granted to ADMIN role\n";

// Find all users with ADMIN role
$adminUsers = User::role('ADMIN')->get();

echo "\nFound " . $adminUsers->count() . " user(s) with ADMIN role:\n";

foreach ($adminUsers as $user) {
    echo "  - {$user->name} ({$user->email})\n";
    
    // Ensure user has ADMIN role
    if (!$user->hasRole('ADMIN')) {
        $user->assignRole('ADMIN');
        echo "    ✓ ADMIN role assigned\n";
    }
    
    // Grant all permissions directly to user (in addition to role permissions)
    $user->syncPermissions(Permission::all());
    echo "    ✓ All permissions granted directly\n";
}

echo "\n✅ Done! All ADMIN users now have Greenhouse Management permissions.\n";
echo "\nPermissions granted:\n";
foreach ($permissions as $perm) {
    echo "  - {$perm}\n";
}
echo "\n";

