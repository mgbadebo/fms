# PHP Version Check and Switch Guide

Your server currently has PHP 8.0.30, but Laravel 12 requires PHP 8.2+.

## Step 1: Check Available PHP Versions

Run these commands on your server to see what PHP versions are available:

```bash
# Check current PHP version
php -v

# Check if PHP 8.2+ is available (common locations)
ls -la /usr/bin/php* 2>/dev/null
ls -la /usr/php*/usr/bin/php* 2>/dev/null
which -a php

# Or check via hosting control panel
# Look for "PHP Version" or "Select PHP Version" option
```

## Step 2: Switch to PHP 8.2+ (if available)

### Option A: Using Command Line (if you have access)

```bash
# If using alternatives system
sudo update-alternatives --config php

# Or if using specific PHP paths
export PATH="/usr/php82/usr/bin:$PATH"
php -v  # Verify it's now 8.2+
```

### Option B: Using Hosting Control Panel

1. Log into your hosting control panel (cPanel, Plesk, etc.)
2. Find "Select PHP Version" or "PHP Version" option
3. Select PHP 8.2 or higher
4. Apply changes

### Option C: Using .htaccess (Apache only)

If your hosting supports it, create/edit `.htaccess` in the project root:

```apache
# AddHandler application/x-httpd-php82 .php
# Or try:
# AddHandler application/x-httpd-php82 .php
```

## Step 3: Verify PHP Version

After switching, verify:

```bash
php -v
# Should show PHP 8.2.x or higher
```

## Step 4: Install Composer Dependencies

Once PHP 8.2+ is active:

```bash
cd /home/sites/25b/b/ba662d9635/fms
php -v  # Confirm PHP 8.2+
composer install --no-dev --optimize-autoloader
```

## If PHP 8.2+ is NOT Available

If your hosting provider doesn't offer PHP 8.2+, you have two options:

### Option 1: Request PHP Upgrade
Contact your hosting provider and request PHP 8.2 or 8.3 to be enabled.

### Option 2: Downgrade Laravel (Not Recommended)
This would require downgrading to Laravel 10, which supports PHP 8.1+. However, this project was built for Laravel 12.

## Quick Test Script

Run this on your server to check everything:

```bash
#!/bin/bash
echo "=== PHP Version Check ==="
php -v
echo ""
echo "=== Composer Version ==="
composer --version
echo ""
echo "=== Checking for PHP 8.2+ ==="
php -r "echo version_compare(PHP_VERSION, '8.2.0', '>=') ? '✓ PHP 8.2+ available' : '✗ PHP 8.2+ required'; echo PHP_EOL;"
```

Save as `check-php.sh`, make executable (`chmod +x check-php.sh`), and run it.

