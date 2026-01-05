<?php

/**
 * Script to grant ADMIN role to a user
 * 
 * Usage: php grant-admin.php admin@owofarms.com.ng
 * Or edit the email below and run: php grant-admin.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Get email from command line argument or use default
$email = $argv[1] ?? 'admin@owofarms.com.ng';

echo "🔍 Looking for user: {$email}\n";

$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ User not found: {$email}\n";
    echo "Creating user...\n";
    
    $user = User::create([
        'name' => 'Admin User',
        'email' => $email,
        'password' => \Illuminate\Support\Facades\Hash::make('password'), // Change this password!
    ]);
    
    echo "✅ User created: {$email}\n";
    echo "⚠️  Default password is 'password' - please change it!\n";
}

// Ensure ADMIN role exists
$adminRole = Role::firstOrCreate(['name' => 'ADMIN', 'guard_name' => 'web']);

// Assign ADMIN role
if (!$user->hasRole('ADMIN')) {
    $user->assignRole('ADMIN');
    echo "✅ ADMIN role assigned to: {$email}\n";
} else {
    echo "ℹ️  User already has ADMIN role: {$email}\n";
}

// Ensure ADMIN has all permissions
$allPermissions = \Spatie\Permission\Models\Permission::all();
if ($allPermissions->count() > 0) {
    $adminRole->syncPermissions($allPermissions);
    echo "✅ ADMIN role now has all {$allPermissions->count()} permissions\n";
}

echo "\n✅ Done! User {$email} now has ADMIN access.\n";
echo "📝 Please change the password if this is a new user.\n";

