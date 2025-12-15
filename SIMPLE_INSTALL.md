# Simple Installation Steps

## Step 1: Upload and Run the Install Script

On your server, run:

```bash
cd /home/sites/25b/b/ba662d9635/fms
chmod +x INSTALL_DEPENDENCIES.sh
./INSTALL_DEPENDENCIES.sh
```

## Step 2: Manual Installation (if script doesn't work)

Run these commands **one at a time**:

```bash
# 1. Navigate to project
cd /home/sites/25b/b/ba662d9635/fms

# 2. Check PHP version (must be 8.2+)
php -v

# 3. Find where composer is
which composer

# 4. Run composer with explicit PHP path
php /usr/local/bin/composer install --no-dev --optimize-autoloader
```

If `/usr/local/bin/composer` doesn't exist, find where composer is:

```bash
# Find composer location
which composer
# This will show something like: /usr/bin/composer or /home/user/bin/composer

# Then use that path:
php /usr/bin/composer install --no-dev --optimize-autoloader
```

## Step 3: If Still Getting Errors

Try this format (replace paths with your actual paths):

```bash
# Format: [PHP_PATH] [COMPOSER_PATH] install --no-dev --optimize-autoloader

# Example 1:
/usr/php83/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader

# Example 2:
/usr/bin/php83 /usr/bin/composer install --no-dev --optimize-autoloader

# Example 3 (if composer.phar is in project):
php composer.phar install --no-dev --optimize-autoloader
```

## Common Composer Locations

- `/usr/local/bin/composer`
- `/usr/bin/composer`
- `~/bin/composer`
- `./composer.phar` (in project directory)

## After Successful Installation

```bash
# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Set permissions
chmod -R 755 storage bootstrap/cache
```

