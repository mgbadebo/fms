# Grant Admin Access

## Quick Method (Run on Server)

SSH into your server and run:

```bash
cd /var/www/fms  # or wherever your app is located
php grant-admin.php admin@owofarms.com.ng
```

Or if the user doesn't exist, it will create them with password "password" (change it immediately!).

## Alternative: Using Artisan Tinker

```bash
php artisan tinker
```

Then run:
```php
$user = \App\Models\User::where('email', 'admin@owofarms.com.ng')->first();
if (!$user) {
    $user = \App\Models\User::create([
        'name' => 'Admin User',
        'email' => 'admin@owofarms.com.ng',
        'password' => \Illuminate\Support\Facades\Hash::make('your-secure-password'),
    ]);
}
$user->assignRole('ADMIN');

// Ensure ADMIN has all permissions
$adminRole = \Spatie\Permission\Models\Role::where('name', 'ADMIN')->first();
$allPermissions = \Spatie\Permission\Models\Permission::all();
$adminRole->syncPermissions($allPermissions);
```

## Alternative: Using Database Directly

If you have direct database access:

```sql
-- Find user ID
SELECT id, email FROM users WHERE email = 'admin@owofarms.com.ng';

-- Find ADMIN role ID
SELECT id, name FROM roles WHERE name = 'ADMIN';

-- Assign role (replace USER_ID and ROLE_ID with actual IDs)
INSERT INTO model_has_roles (role_id, model_type, model_id)
VALUES (ROLE_ID, 'App\\Models\\User', USER_ID)
ON DUPLICATE KEY UPDATE role_id = role_id;
```

