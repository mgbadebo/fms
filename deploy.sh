#!/bin/bash

# Farm Management System - Deployment Script
# This script pulls the latest code from GitHub and deploys it

set -e

echo "🚀 Starting deployment..."

# Change to application directory
cd /var/www/fms || cd /home/fms/fms || { echo "❌ Application directory not found!"; exit 1; }

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin main || git pull origin master

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear and cache configuration
echo "⚙️  Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear application cache
php artisan cache:clear

# Restart PHP-FPM
echo "🔄 Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm || sudo systemctl restart php-fpm || true

# Restart queue workers (if configured)
if systemctl is-active --quiet fms-worker; then
    echo "🔄 Restarting queue workers..."
    sudo systemctl restart fms-worker
fi

# Set permissions
echo "🔐 Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "✅ Deployment complete!"

# Show current version/commit
echo ""
echo "📌 Current version:"
git log -1 --oneline

