@echo off
REM License Platform - Initial Setup Script (Windows)
REM This script sets up the database and runs initial migrations

echo ===================================
echo License Platform - Initial Setup
echo ===================================
echo.

REM Check if MySQL is accessible
echo Checking MySQL connection...
mysql -u root -e "SELECT 1;" >nul 2>&1
if %errorlevel% neq 0 (
    echo X Cannot connect to MySQL. Please check your credentials.
    pause
    exit /b 1
)
echo √ MySQL connection successful
echo.

REM Create database
echo Creating database 'license_platform'...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS license_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% equ 0 (
    echo √ Database created successfully
) else (
    echo X Failed to create database
    pause
    exit /b 1
)
echo.

REM Check Redis
echo Checking Redis connection...
redis-cli ping >nul 2>&1
if %errorlevel% equ 0 (
    echo √ Redis is running
) else (
    echo ! Redis is not running. Please start Redis server.
    echo   Queue and cache features will not work without Redis.
)
echo.

REM Run migrations
echo Running database migrations...
php artisan migrate --force
if %errorlevel% equ 0 (
    echo √ Migrations completed successfully
) else (
    echo X Migration failed
    pause
    exit /b 1
)
echo.

echo ===================================
echo Setup completed successfully! √
echo ===================================
echo.
echo Next steps:
echo 1. Start queue worker: php artisan queue:work redis
echo 2. Start scheduler: php artisan schedule:work
echo 3. Start dev server: php artisan serve
echo.
pause
