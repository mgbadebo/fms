# How to Create User Login Credentials

There are several ways to create user accounts in the Farm Management System:

## Method 1: Using Artisan Command (Recommended)

The easiest way is to use the interactive command:

```bash
php artisan user:create --interactive
```

This will prompt you for:
- Name
- Email
- Password (with confirmation)
- Role (optional: OWNER, MANAGER, WORKER, FINANCE, AUDITOR, ADMIN)
- Farm ID (optional: to attach user to a farm)

### Non-Interactive Mode

You can also create users directly with options:

```bash
php artisan user:create --name="John Doe" --email="john@example.com" --password="securepassword123" --role="MANAGER" --farm=1
```

### Examples

**Create a farm manager:**
```bash
php artisan user:create --name="Farm Manager" --email="manager@farm.com" --password="manager123" --role="MANAGER" --farm=1
```

**Create a worker:**
```bash
php artisan user:create --name="Field Worker" --email="worker@farm.com" --password="worker123" --role="WORKER" --farm=1
```

**Create an admin:**
```bash
php artisan user:create --name="System Admin" --email="admin@farm.com" --password="admin123" --role="ADMIN"
```

## Method 2: Using Laravel Tinker

```bash
php artisan tinker
```

Then in the tinker console:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Create user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password123'),
]);

// Assign role
$role = Role::where('name', 'MANAGER')->first();
$user->assignRole($role);

// Attach to farm (optional)
$farm = \App\Models\Farm::find(1);
$farm->users()->attach($user->id, ['role' => 'MANAGER']);
```

## Method 3: Using API Registration Endpoint

You can register users via the API:

```bash
curl -X POST https://yourdomain.com/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Note:** This creates a user but doesn't assign roles or attach to farms. You'll need to do that separately via the API or using tinker.

## Method 4: Using Database Seeder

You can add users to `database/seeders/DatabaseSeeder.php`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password123'),
]);

$user->assignRole('MANAGER');
```

Then run:
```bash
php artisan db:seed
```

## Available Roles

- **OWNER**: Farm owner with full access
- **MANAGER**: Farm manager with operational access
- **WORKER**: Field worker with limited access
- **FINANCE**: Finance/accounting access
- **AUDITOR**: Read-only audit access
- **ADMIN**: System administrator with full access

## Default Admin Account

After running the seeder, a default admin account is created:

- **Email**: `admin@fms.test`
- **Password**: `password`

**Important:** Change this password in production!

## Changing User Password

To change a user's password:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'user@example.com')->first();
$user->password = Hash::make('newpassword123');
$user->save();
```

## Listing All Users

To see all users:

```bash
php artisan tinker
```

```php
\App\Models\User::all(['id', 'name', 'email'])->toArray();
```

## Troubleshooting

### User Already Exists
If you get "User already exists", either:
- Use a different email
- Or update the existing user instead of creating a new one

### Role Not Found
Make sure roles are created. Run:
```bash
php artisan db:seed
```

This creates all default roles.

### Can't Login
- Verify the password is correct
- Check that the user exists: `php artisan tinker` then `User::where('email', 'email@example.com')->first()`
- Make sure the user has a role assigned (some features may require roles)

