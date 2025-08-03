#!/bin/bash

# Render build script for CRMAIze
echo "🚀 Starting CRMAIze build process..."

# Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p data cache cache/twig public/assets/icons templates/emails

# Set permissions
echo "🔐 Setting permissions..."
chmod 755 data cache
chmod -R 755 public/assets

# Run installation script
echo "⚙️ Running installation script..."
php scripts/install.php

# Setup demo users if not exists
echo "👥 Setting up demo users..."
if [ -f "scripts/setup_users.php" ]; then
    php scripts/setup_users.php
fi

# Import sample data if not exists
echo "📊 Importing sample data..."
if [ -f "scripts/import_data.php" ]; then
    php scripts/import_data.php
fi

echo "✅ Build completed successfully!"