#!/bin/bash
# PHP Version Check Script for FMS Deployment

echo "=== PHP Version Check ==="
php -v
echo ""

echo "=== Composer Version ==="
composer --version
echo ""

echo "=== PHP Version Requirement Check ==="
PHP_VERSION=$(php -r "echo PHP_VERSION;")
REQUIRED_VERSION="8.2.0"

if php -r "exit(version_compare(PHP_VERSION, '$REQUIRED_VERSION', '>=') ? 0 : 1);"; then
    echo "✓ PHP version $PHP_VERSION meets requirement (>= $REQUIRED_VERSION)"
    echo ""
    echo "=== Ready to Install Dependencies ==="
    echo "Run: composer install --no-dev --optimize-autoloader"
else
    echo "✗ PHP version $PHP_VERSION does NOT meet requirement (>= $REQUIRED_VERSION)"
    echo ""
    echo "=== Action Required ==="
    echo "You need to switch to PHP 8.2 or higher."
    echo "Check available PHP versions:"
    echo "  ls -la /usr/bin/php*"
    echo "  ls -la /usr/php*/usr/bin/php*"
    echo ""
    echo "Or use your hosting control panel to select PHP 8.2+"
fi

echo ""
echo "=== Checking Common PHP Locations ==="
for path in /usr/bin/php* /usr/php*/usr/bin/php* /opt/php*/bin/php*; do
    if [ -f "$path" ]; then
        echo "Found: $path"
        $path -v 2>/dev/null | head -1
    fi
done 2>/dev/null

