# Seeding Site Types on Server

This document explains how to ensure site types are seeded on the server so they appear in the site dropdown field.

## Quick Fix

Run this command on your server:

```bash
cd /var/www/fms  # or /home/fms/fms depending on your setup
php artisan db:seed --class=SiteTypeSeeder --force
```

Or use the provided script:

```bash
./seed-site-types.sh
```

## What Gets Seeded

The seeder will create/update these 5 default site types:

1. **Farmland** (code: `farmland`, prefix: `FL`)
2. **Warehouse** (code: `warehouse`, prefix: `WH`)
3. **Factory** (code: `factory`, prefix: `FT`)
4. **Greenhouse** (code: `greenhouse`, prefix: `GH`)
5. **Estate** (code: `estate`, prefix: `EST`)

## Automatic Seeding

Site types are automatically seeded when you run:

```bash
php artisan db:seed
```

This runs the `DatabaseSeeder`, which calls `SiteTypeSeeder`.

## Verification

To verify site types are seeded, you can:

1. **Check via API:**
   ```bash
   curl http://your-domain.com/api/v1/site-types
   ```

2. **Check via Tinker:**
   ```bash
   php artisan tinker
   >>> App\Models\SiteType::all()->pluck('name', 'code')
   ```

3. **Check in Admin UI:**
   - Navigate to `/admin/site-types`
   - You should see all 5 site types listed

## Troubleshooting

### Site types not showing in dropdown

1. **Check if site_types table exists:**
   ```bash
   php artisan migrate:status | grep site_types
   ```

2. **Run migrations if needed:**
   ```bash
   php artisan migrate --force
   ```

3. **Seed site types:**
   ```bash
   php artisan db:seed --class=SiteTypeSeeder --force
   ```

4. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### After deployment

The deployment script (`deploy.sh`) has been updated to automatically seed site types. If you're using manual deployment, make sure to run:

```bash
php artisan migrate --force
php artisan db:seed --class=SiteTypeSeeder --force
```

## Notes

- The seeder uses `updateOrCreate`, so it's safe to run multiple times
- It will update existing site types if their data has changed
- It will create new site types if they don't exist
- Site types are required for creating new sites
