# Production Server Update Guide

After pulling changes from GitHub, you need to rebuild the frontend assets and clear Laravel caches.

## Quick Update Steps

Run these commands on your production server:

```bash
# Navigate to your application directory
cd /path/to/your/fms

# Pull latest changes (if not already done)
git pull origin main

# Install/update npm dependencies (if package.json changed)
npm install

# Build the frontend assets
npm run build

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild caches (optional, for better performance)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM (if needed)
sudo systemctl restart php8.2-fpm
# or
sudo systemctl restart php-fpm
```

## Verify Build Files

Check that the build files exist:

```bash
ls -la public/build/
```

You should see:
- `manifest.json`
- `assets/` directory with CSS and JS files

## If npm is not available on server

If Node.js/npm is not installed on your production server, you have two options:

### Option 1: Build locally and upload

```bash
# On your local machine
npm run build

# Then upload the public/build/ directory to your server
# You can use scp, rsync, or your preferred method
scp -r public/build/* user@your-server:/path/to/fms/public/build/
```

### Option 2: Install Node.js on server

See `INSTALL_NODE_NPM.md` for instructions.

## Troubleshooting

### Changes still not showing?

1. **Hard refresh your browser**: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
2. **Check browser console**: Look for 404 errors on assets
3. **Verify build files**: Ensure `public/build/manifest.json` exists
4. **Check file permissions**: 
   ```bash
   chmod -R 755 public/build
   chown -R www-data:www-data public/build
   ```

### Build fails?

- Check Node.js version: `node -v` (should be 18+)
- Check npm version: `npm -v`
- Try deleting `node_modules` and reinstalling:
  ```bash
  rm -rf node_modules package-lock.json
  npm install
  npm run build
  ```

