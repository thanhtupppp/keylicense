<?php

/**
 * Setup script for integration test database.
 * Run this before running integration tests.
 */

require __DIR__ . '/../../vendor/autoload.php';

$host = '127.0.0.1';
$port = 3306;
$username = 'root';
$password = '';
$testDatabase = 'license_platform_test';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create test database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    echo "✓ Test database '{$testDatabase}' created successfully\n";

    // Verify connection to test database
    $testPdo = new PDO("mysql:host={$host};port={$port};dbname={$testDatabase}", $username, $password);
    echo "✓ Successfully connected to test database\n";

    echo "\nTest database is ready for integration tests.\n";
    echo "Run: php artisan test --testsuite=Integration\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nPlease ensure:\n";
    echo "1. MySQL is running on {$host}:{$port}\n";
    echo "2. User '{$username}' has permission to create databases\n";
    exit(1);
}
