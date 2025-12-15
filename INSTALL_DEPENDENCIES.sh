#!/bin/bash
# Simple script to install Composer dependencies with PHP 8.3

echo "=== Finding PHP 8.3 and Composer ==="

# Find PHP 8.3 path
if command -v php &> /dev/null; then
    PHP_PATH=$(which php)
    PHP_VERSION=$(php -v | head -1)
    echo "Found PHP: $PHP_PATH"
    echo "Version: $PHP_VERSION"
else
    echo "Error: PHP not found in PATH"
    exit 1
fi

# Verify it's PHP 8.3+
if ! php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
    echo "Error: PHP version must be 8.2 or higher"
    echo "Current version: $(php -r 'echo PHP_VERSION;')"
    exit 1
fi

# Find Composer
if command -v composer &> /dev/null; then
    COMPOSER_PATH=$(which composer)
    echo "Found Composer: $COMPOSER_PATH"
elif [ -f "/usr/local/bin/composer" ]; then
    COMPOSER_PATH="/usr/local/bin/composer"
    echo "Found Composer: $COMPOSER_PATH"
elif [ -f "$HOME/.composer/vendor/bin/composer" ]; then
    COMPOSER_PATH="$HOME/.composer/vendor/bin/composer"
    echo "Found Composer: $COMPOSER_PATH"
else
    echo "Error: Composer not found"
    echo "Trying to find composer.phar..."
    if [ -f "composer.phar" ]; then
        COMPOSER_PATH="./composer.phar"
        echo "Found composer.phar in current directory"
    else
        echo "Please install Composer first"
        exit 1
    fi
fi

echo ""
echo "=== Installing Dependencies ==="
echo "Running: $PHP_PATH $COMPOSER_PATH install --no-dev --optimize-autoloader"
echo ""

# Run Composer with explicit PHP path
$PHP_PATH $COMPOSER_PATH install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Dependencies installed successfully!"
else
    echo ""
    echo "✗ Installation failed. Check the error messages above."
    exit 1
fi

