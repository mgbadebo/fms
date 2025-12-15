# How to Switch PHP Version on Your Server

## Current Issue
- **Current PHP**: 8.0.30
- **Required PHP**: 8.2 or higher
- **Error**: Composer cannot install because PHP version is too old

## Solution: Switch to PHP 8.2+

### Method 1: Using Hosting Control Panel (Recommended)

1. **Log into your hosting control panel** (cPanel, Plesk, or your provider's panel)
2. **Find "Select PHP Version"** or **"PHP Version"** option
   - Usually in "Software" or "PHP Settings" section
3. **Select PHP 8.2** or **PHP 8.3** (whichever is available)
4. **Click "Apply"** or "Save"
5. **Wait a few seconds** for the change to take effect

### Method 2: Using Command Line

Based on your path structure (`/usr/php80/usr/bin/php`), try:

```bash
# Check available PHP versions
ls -la /usr/php*/usr/bin/php*

# You might see:
# /usr/php80/usr/bin/php  (current - 8.0)
# /usr/php82/usr/bin/php  (8.2 - if available)
# /usr/php83/usr/bin/php  (8.3 - if available)

# Switch to PHP 8.2 (if available)
export PATH="/usr/php82/usr/bin:$PATH"

# Verify the switch worked
php -v
# Should show PHP 8.2.x or 8.3.x
```

### Method 3: Using .htaccess (Apache only)

If your hosting supports it, create/edit `.htaccess` in your project root:

```apache
# For PHP 8.2
AddHandler application/x-httpd-php82 .php

# Or for PHP 8.3
# AddHandler application/x-httpd-php83 .php
```

### Method 4: Contact Your Hosting Provider

If none of the above work, contact your hosting provider and ask:
- "Can you enable PHP 8.2 or 8.3 for my account?"
- "How do I switch PHP versions on your platform?"

## After Switching PHP Version

Once PHP 8.2+ is active:

```bash
# 1. Verify PHP version
php -v
# Must show PHP 8.2.x or higher

# 2. Navigate to project
cd /home/sites/25b/b/ba662d9635/fms

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Continue with setup
cp .env.example .env
php artisan key:generate
php artisan migrate --force
```

## Quick Test

Run this to check if PHP 8.2+ is now active:

```bash
php -r "echo version_compare(PHP_VERSION, '8.2.0', '>=') ? '✓ PHP 8.2+ is active!' : '✗ Still on PHP ' . PHP_VERSION . ' - need to switch';"
```

## Common Hosting Providers

### cPanel
- **Location**: Software → Select PHP Version
- **Action**: Select PHP 8.2 or 8.3, click "Set as current"

### Plesk
- **Location**: Websites & Domains → PHP Settings
- **Action**: Select PHP 8.2 or 8.3 from dropdown

### Cloudways / Managed Hosting
- Usually done via their dashboard under "Application Settings"

---

**Important**: You MUST switch to PHP 8.2+ before `composer install` will work!

