# Deployment Steps for Bell Pepper Inventory Update

## What Changed
- New migration: `add_storage_location_id_to_production_cycle_harvest_crates_table`
- Updated React components: `HarvestRecords.jsx`, `BellPepperInventory.jsx`
- New controller: `BellPepperInventoryController.php`
- New controller: `InventoryLocationController.php`
- Updated config: `app.php` (app name changed to "Ogenki Farms")
- Updated routes and models

## Deployment Steps

### Step 1: Push to Git (Local Machine)
```bash
git add .
git commit -m "Add Bell Pepper inventory system with storage location tracking"
git push origin main
```

### Step 2: On Production Server

#### Option A: Using the deploy.sh script (Recommended)
```bash
cd /var/www/fms  # or wherever your app is located
./deploy.sh
```

#### Option B: Manual Deployment
```bash
# Navigate to application directory
cd /var/www/fms  # or /home/fms/fms

# Pull latest changes
git pull origin main

# Install/update PHP dependencies
composer install --no-dev --optimize-autoloader

# Run the new migration (IMPORTANT!)
php artisan migrate --force

# Install/update npm dependencies (if package.json changed)
npm install

# Build frontend assets (REQUIRED - we changed React components)
npm run build

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild caches for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
# or
sudo systemctl restart php-fpm

# Restart queue workers (if you have them)
sudo systemctl restart fms-worker  # if configured
```

### Step 3: Verify Deployment

1. **Check migration ran successfully:**
   ```bash
   php artisan migrate:status
   ```
   You should see the migration `2026_01_13_133929_add_storage_location_id_to_production_cycle_harvest_crates_table` as "Ran"

2. **Check frontend build:**
   ```bash
   ls -la public/build/
   ```
   Should show `manifest.json` and `assets/` directory

3. **Test the application:**
   - Visit your site and check the page title shows "Ogenki Farms"
   - Try accessing the Bell Pepper Inventory page
   - Try adding a crate to a harvest record - storage location should be required

### Important Notes

⚠️ **Migration Required**: The new migration adds `storage_location_id` column. Make sure it runs successfully.

⚠️ **Frontend Build Required**: Since we modified React components, you MUST run `npm run build` on the server (or build locally and upload).

⚠️ **If npm is not on server**: Build locally and upload:
```bash
# On your local machine
npm run build

# Upload to server
scp -r public/build/* user@your-server:/path/to/fms/public/build/
```

### Troubleshooting

**Migration fails?**
- Check database connection
- Verify you have backup before running migration
- Check Laravel logs: `storage/logs/laravel.log`

**Frontend not updating?**
- Hard refresh browser: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- Check browser console for 404 errors
- Verify `public/build/manifest.json` exists
- Check file permissions: `chmod -R 755 public/build`

**Config not updating?**
- Run: `php artisan config:clear && php artisan config:cache`
- Check `.env` file has `APP_NAME=Ogenki Farms` (optional, config default is now "Ogenki Farms")
