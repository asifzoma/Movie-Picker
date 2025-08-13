#!/bin/bash

# Movie Picker - cPanel Deployment Script
# This script removes unnecessary files and prepares your project for cPanel hosting

echo "🎬 Movie Picker - Preparing for cPanel Deployment"
echo "=================================================="

# Remove Composer files
echo "🗑️  Removing Composer dependencies..."
rm -rf vendor/
rm -f composer.json
rm -f composer.lock

# Remove Node.js files
echo "🗑️  Removing Node.js dependencies..."
rm -rf node_modules/
rm -f package.json
rm -f package-lock.json
rm -f tailwind.config.js

# Remove development files
echo "🗑️  Removing development files..."
rm -f php_errors.log
rm -f debug.html
rm -f test.html
rm -f .gitattributes

# Create production-ready directory
echo "📁 Creating production directory..."
mkdir -p ../Movie-Picker-Production
cp -r index.php ../Movie-Picker-Production/
cp -r api.php ../Movie-Picker-Production/
cp -r config.php ../Movie-Picker-Production/
cp -r SessionManager.php ../Movie-Picker-Production/
cp -r RecommendationEngine.php ../Movie-Picker-Production/
cp -r .env ../Movie-Picker-Production/
cp -r .env.example ../Movie-Picker-Production/
cp -r css/ ../Movie-Picker-Production/
cp -r js/ ../Movie-Picker-Production/
cp -r data/ ../Movie-Picker-Production/
cp -r styles/ ../Movie-Picker-Production/
cp -r DEPLOYMENT.md ../Movie-Picker-Production/

# Set proper permissions
echo "🔐 Setting proper permissions..."
chmod 755 ../Movie-Picker-Production/data/
chmod 644 ../Movie-Picker-Production/.env

echo ""
echo "✅ Deployment package ready!"
echo "📦 Production files are in: ../Movie-Picker-Production/"
echo "📋 See DEPLOYMENT.md for deployment instructions"
echo ""
echo "🚀 Ready to upload to cPanel!"
