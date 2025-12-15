#!/bin/bash
# Script to regenerate composer.lock for PHP 8.3 compatibility

echo "=== Regenerating Composer Lock File for PHP 8.3 ==="
echo ""

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "Current PHP version: $PHP_VERSION"

if ! php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"; then
    echo "Error: PHP 8.3 or higher is required"
    exit 1
fi

# Find Composer
if command -v composer &> /dev/null; then
    COMPOSER_PATH=$(which composer)
elif [ -f "/usr/local/bin/composer" ]; then
    COMPOSER_PATH="/usr/local/bin/composer"
elif [ -f "composer.phar" ]; then
    COMPOSER_PATH="./composer.phar"
else
    echo "Error: Composer not found"
    exit 1
fi

echo "Using Composer: $COMPOSER_PATH"
echo ""

# Backup existing lock file
if [ -f "composer.lock" ]; then
    echo "Backing up existing composer.lock..."
    cp composer.lock composer.lock.backup
    echo "Backup saved as composer.lock.backup"
    echo ""
fi

# Delete lock file
echo "Removing old composer.lock..."
rm -f composer.lock
echo ""

# Regenerate lock file with PHP 8.3
echo "Regenerating composer.lock with PHP 8.3 compatible versions..."
echo "Running: php $COMPOSER_PATH install --no-dev --optimize-autoloader"
echo ""

php $COMPOSER_PATH install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Lock file regenerated successfully!"
    echo "✓ Dependencies installed!"
    echo ""
    echo "Next steps:"
    echo "  1. cp .env.example .env"
    echo "  2. php artisan key:generate"
    echo "  3. php artisan migrate --force"
else
    echo ""
    echo "✗ Failed to regenerate lock file"
    echo "Restoring backup..."
    if [ -f "composer.lock.backup" ]; then
        mv composer.lock.backup composer.lock
    fi
    exit 1
fi

