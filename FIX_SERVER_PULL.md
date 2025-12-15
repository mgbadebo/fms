# Fix: Server Pull Conflict with composer.lock

## Problem
When pulling on the server, you get:
```
error: Your local changes to the following files would be overwritten by merge:
	composer.lock
```

## Solution Options

### Option 1: Stash Local Changes (Recommended)

This saves your local changes in case you need them later:

```bash
cd /home/sites/25b/b/ba662d9635/fms

# Stash local changes
git stash

# Pull the latest changes
git pull origin main

# If you need your stashed changes back later:
# git stash pop
```

### Option 2: Discard Local Changes (Use Remote Version)

If you want to use the remote version and don't need local changes:

```bash
cd /home/sites/25b/b/ba662d9635/fms

# Discard local changes to composer.lock
git checkout -- composer.lock

# Or if composer.lock doesn't exist locally, remove it:
# rm composer.lock

# Pull the latest changes
git pull origin main
```

### Option 3: Commit Local Changes First

If your local composer.lock has important changes:

```bash
cd /home/sites/25b/b/ba662d9635/fms

# Commit the local changes
git add composer.lock
git commit -m "Update composer.lock for server environment"

# Pull (this will create a merge commit if needed)
git pull origin main

# If there are conflicts, resolve them and commit
```

## Recommended: Option 1 (Stash)

Since `composer.lock` should be generated from `composer.json`, the safest approach is:

```bash
cd /home/sites/25b/b/ba662d9635/fms

# Stash any local changes
git stash

# Pull latest code
git pull origin main

# Regenerate composer.lock with correct PHP version
php /usr/local/bin/composer install --no-dev --optimize-autoloader
```

This ensures you have the latest code and a `composer.lock` compatible with your server's PHP version.

## After Pulling

Once you've successfully pulled:

```bash
# Install/update dependencies
php /usr/local/bin/composer install --no-dev --optimize-autoloader

# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Run migrations if needed
php artisan migrate --force
```

