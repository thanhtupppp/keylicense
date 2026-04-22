#!/bin/bash

# License Platform - Initial Setup Script
# This script sets up the database and runs initial migrations

echo "==================================="
echo "License Platform - Initial Setup"
echo "==================================="
echo ""

# Check if MySQL is accessible
echo "Checking MySQL connection..."
mysql -u root -p -e "SELECT 1;" > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "❌ Cannot connect to MySQL. Please check your credentials."
    exit 1
fi
echo "✅ MySQL connection successful"
echo ""

# Create database
echo "Creating database 'license_platform'..."
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS license_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if [ $? -eq 0 ]; then
    echo "✅ Database created successfully"
else
    echo "❌ Failed to create database"
    exit 1
fi
echo ""

# Check Redis
echo "Checking Redis connection..."
redis-cli ping > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Redis is running"
else
    echo "⚠️  Redis is not running. Please start Redis server."
    echo "   Queue and cache features will not work without Redis."
fi
echo ""

# Run migrations
echo "Running database migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo "✅ Migrations completed successfully"
else
    echo "❌ Migration failed"
    exit 1
fi
echo ""

echo "==================================="
echo "Setup completed successfully! ✅"
echo "==================================="
echo ""
echo "Next steps:"
echo "1. Start queue worker: php artisan queue:work redis"
echo "2. Start scheduler: php artisan schedule:work"
echo "3. Start dev server: php artisan serve"
echo ""
