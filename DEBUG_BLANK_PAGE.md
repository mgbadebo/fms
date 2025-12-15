# Debug: Blank Page After Login

## Quick Debug Steps

### Step 1: Visit Debug Page

After pulling the latest code, visit:
```
https://yourdomain.com/debug
```

This will show you:
- ✓ If the app element exists
- ✓ If Vite assets are loading
- ✗ Any JavaScript errors
- ✓ If API is accessible

### Step 2: Check Browser Console

1. Open your site
2. Press **F12** (or right-click → Inspect)
3. Go to **Console** tab
4. Look for **red errors**
5. Copy any error messages

### Step 3: Check Network Tab

1. In DevTools, go to **Network** tab
2. Refresh the page
3. Look for:
   - **Red/failed requests** (especially to `/build/` or `/api/`)
   - **404 errors** (files not found)
   - **500 errors** (server errors)

### Step 4: Check if Frontend Files Exist

On your server, verify build files exist:

```bash
cd /home/sites/25b/b/ba662d9635/fms
ls -la public/build/
```

You should see:
- `manifest.json`
- `assets/` directory with JS and CSS files

If missing, you need to build:
```bash
npm run build
# Or upload public/build/ from your local machine
```

## Common Issues & Fixes

### Issue 1: "Cannot find module" or "Failed to load module"

**Cause:** Frontend build files missing or not uploaded

**Fix:**
```bash
# On server (if you have npm):
npm install
npm run build

# Or upload public/build/ from local machine
```

### Issue 2: "App element not found"

**Cause:** The HTML template isn't loading correctly

**Fix:**
- Check `resources/views/app.blade.php` exists
- Clear view cache: `php artisan view:clear`
- Check web server is serving Laravel correctly

### Issue 3: "401 Unauthorized" in Network tab

**Cause:** Token not being sent or invalid

**Fix:**
1. Check localStorage in browser:
   - Press F12 → Application tab → Local Storage
   - Look for `token` key
2. If missing, login again
3. Check token format in Network tab headers

### Issue 4: "CORS error" or "Network error"

**Cause:** API URL mismatch or CORS not configured

**Fix:**
- Check `.env` has correct `APP_URL`
- Make sure API calls use relative URLs (they should)
- Check server allows requests from your domain

### Issue 5: White screen with no errors

**Cause:** React app not mounting or silent error

**Fix:**
1. Check browser console for warnings (yellow)
2. Check if React DevTools shows the app
3. Try visiting `/debug` page
4. Check if `public/build/manifest.json` is accessible

## Step-by-Step Fix

### 1. Pull Latest Code

```bash
cd /home/sites/25b/b/ba662d9635/fms
git pull origin main
```

### 2. Rebuild Frontend

**Option A: If you have npm on server:**
```bash
npm install
npm run build
```

**Option B: Build locally and upload:**
```bash
# On your local machine:
cd /Users/mosesgbadebo/FMS
npm run build
# Then upload public/build/ to server
```

### 3. Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
```

### 4. Check File Permissions

```bash
chmod -R 755 public/build/
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 5. Test Debug Page

Visit: `https://yourdomain.com/debug`

This will show you exactly what's wrong.

### 6. Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

Then try to access the site and watch for errors.

## Still Not Working?

### Check These:

1. **Is the HTML loading?**
   - View page source (Ctrl+U)
   - Look for `<div id="app"></div>`
   - Look for Vite script tags

2. **Are JavaScript files loading?**
   - In Network tab, check if `.js` files are loading
   - Check their status codes (should be 200)

3. **Is there a JavaScript error?**
   - Check Console tab
   - Look for red errors
   - Copy the full error message

4. **Is the API working?**
   - Test: `curl https://yourdomain.com/api/v1/login -X POST -H "Content-Type: application/json" -d '{"email":"test","password":"test"}'`
   - Should return JSON (even if 401)

### Get Help

If still stuck, provide:
1. Browser console errors (screenshot or copy)
2. Network tab showing failed requests
3. Output from `/debug` page
4. Laravel log errors (last 50 lines)

