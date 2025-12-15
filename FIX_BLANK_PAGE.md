# Fix: Blank Page After Login

## Common Causes

### 1. Roles Not Created

If you see "Role 'ADMIN' not found", you need to create roles first:

```bash
php artisan db:seed
```

Or run the seeder specifically:
```bash
php artisan db:seed --class=DatabaseSeeder
```

### 2. API Errors

Check browser console (F12) for errors. Common issues:

**CORS Errors:**
- Make sure your `.env` has correct `APP_URL`
- Check that API routes are accessible

**401 Unauthorized:**
- Token might not be stored correctly
- Check localStorage in browser DevTools
- Try logging out and back in

**404 Not Found:**
- API routes might not be registered
- Run: `php artisan route:list | grep api`

**500 Server Error:**
- Check Laravel logs: `storage/logs/laravel.log`
- Check server error logs

### 3. Frontend Build Issues

If the frontend isn't loading:

```bash
# Rebuild frontend
npm run build

# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### 4. Missing Data

If dashboard shows but is empty:

- Create some farms: Use the frontend or API
- Run seeder to get demo data: `php artisan db:seed`

### 5. JavaScript Errors

Open browser console (F12) and check for:
- Syntax errors
- Missing dependencies
- Network errors

## Quick Fixes

### Step 1: Create Roles

```bash
php artisan db:seed
```

### Step 2: Check API is Working

```bash
# Test login endpoint
curl -X POST https://yourdomain.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"your@email.com","password":"yourpassword"}'
```

### Step 3: Check Frontend Build

```bash
# On server, if you have npm:
npm run build

# Or upload built files from local machine
```

### Step 4: Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
```

### Step 5: Check Browser Console

1. Open your site in browser
2. Press F12 to open DevTools
3. Go to Console tab
4. Look for red errors
5. Go to Network tab
6. Refresh page
7. Check if API calls are failing

## Debugging Steps

### Check if User Can Access API

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'your@email.com')->first();
$token = $user->createToken('test')->plainTextToken;
echo $token;
```

Then test with curl:
```bash
curl -X GET https://yourdomain.com/api/v1/farms \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

Then try to access the dashboard and watch for errors.

### Check API Response Format

The dashboard expects:
- Farms: `{ data: [...] }` or `[...]`
- Harvest Lots: `{ data: [...] }` or `[...]`
- Scale Devices: `{ data: [...] }` or `[...]`

If your API returns different format, the dashboard might not display data correctly.

## Still Not Working?

1. **Check browser console** - Most issues show errors there
2. **Check network tab** - See which API calls are failing
3. **Check Laravel logs** - Server-side errors are logged there
4. **Verify .env** - Make sure `APP_URL` matches your domain
5. **Verify database** - Make sure migrations ran: `php artisan migrate:status`

