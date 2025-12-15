# Fix: Composer Using Wrong PHP Version

## Problem
- `php -v` shows PHP 8.3 ✓
- But `composer install` still uses PHP 8.0.30 ✗

## Solution: Run Composer with Explicit PHP Path

### Step 1: Find PHP 8.3 Path

```bash
# Find where PHP 8.3 is located
which php
# Should show something like: /usr/php83/usr/bin/php

# Or check directly
/usr/php83/usr/bin/php -v
```

### Step 2: Run Composer with PHP 8.3

Instead of just `composer install`, use the full PHP path:

```bash
# Option 1: Use full PHP path
/usr/php83/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader

# Option 2: If composer is in a different location, find it first
which composer
# Then use:
/usr/php83/usr/bin/php $(which composer) install --no-dev --optimize-autoloader

# Option 3: If you have php83 command available
php83 $(which composer) install --no-dev --optimize-autoloader
```

### Step 3: Verify Composer is Using Correct PHP

```bash
# Check which PHP Composer will use
/usr/php83/usr/bin/php $(which composer) --version

# Or check the first line of composer executable
head -1 $(which composer)
```

### Step 4: Create an Alias (Optional, for future use)

Add to your `~/.bashrc` or `~/.zshrc`:

```bash
alias composer83='/usr/php83/usr/bin/php /usr/local/bin/composer'
```

Then reload:
```bash
source ~/.bashrc  # or ~/.zshrc
```

Now you can use `composer83 install` instead.

## Quick Fix Command

Run this on your server (adjust paths if needed):

```bash
cd /home/sites/25b/b/ba662d9635/fms

# Find PHP 8.3 path
PHP83_PATH=$(which php)
echo "Using PHP: $PHP83_PATH"

# Find Composer path
COMPOSER_PATH=$(which composer)
echo "Using Composer: $COMPOSER_PATH"

# Run with explicit PHP path
$PHP83_PATH $COMPOSER_PATH install --no-dev --optimize-autoloader
```

## Alternative: Update Composer's PHP Path

If Composer is a PHAR file, you can check its shebang:

```bash
# Check composer's first line
head -1 $(which composer)

# If it points to wrong PHP, you might need to reinstall composer
# Or use the explicit path method above
```

## Verify It Worked

After running with the correct PHP:

```bash
# Should show PHP 8.3 in the output
$PHP83_PATH $COMPOSER_PATH --version
```

