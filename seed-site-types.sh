#!/bin/bash

# Script to seed site types on the server
# This ensures all default site types (Farmland, Warehouse, Factory, Greenhouse, Estate) are available

echo "🌱 Seeding site types..."

# Change to application directory
cd /var/www/fms || cd /home/fms/fms || { echo "❌ Application directory not found!"; exit 1; }

# Run the SiteTypeSeeder
php artisan db:seed --class=SiteTypeSeeder --force

echo "✅ Site types seeded successfully!"
echo ""
echo "📋 Available site types:"
php artisan tinker --execute="echo App\Models\SiteType::all()->pluck('name', 'code')->toJson(JSON_PRETTY_PRINT);"
