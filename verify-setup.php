<?php

/**
 * License Platform - Setup Verification Script
 * 
 * This script verifies that all Task 1 requirements have been completed successfully.
 */

echo "===========================================\n";
echo "License Platform - Setup Verification\n";
echo "===========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check PHP version
echo "Checking PHP version...\n";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    $success[] = "✅ PHP version: " . PHP_VERSION;
} else {
    $errors[] = "❌ PHP version " . PHP_VERSION . " is too old. Requires PHP 8.2+";
}

// Check if .env exists
echo "Checking .env file...\n";
if (file_exists('.env')) {
    $success[] = "✅ .env file exists";

    // Check .env contents
    $env = file_get_contents('.env');

    // Check database configuration
    if (strpos($env, 'DB_CONNECTION=mysql') !== false) {
        $success[] = "✅ MySQL database configured";
    } else {
        $errors[] = "❌ MySQL not configured in .env";
    }

    // Check Redis configuration
    if (strpos($env, 'REDIS_HOST=') !== false) {
        $success[] = "✅ Redis configured";
    } else {
        $errors[] = "❌ Redis not configured in .env";
    }

    // Check queue configuration
    if (strpos($env, 'QUEUE_CONNECTION=redis') !== false) {
        $success[] = "✅ Queue driver set to Redis";
    } else {
        $warnings[] = "⚠️  Queue driver not set to Redis";
    }

    // Check cache configuration
    if (strpos($env, 'CACHE_STORE=redis') !== false) {
        $success[] = "✅ Cache store set to Redis";
    } else {
        $warnings[] = "⚠️  Cache store not set to Redis";
    }

    // Check JWT private key
    if (strpos($env, 'JWT_PRIVATE_KEY=') !== false) {
        $success[] = "✅ JWT private key configured in .env";
    } else {
        $errors[] = "❌ JWT private key not found in .env";
    }

    // Check timezone
    if (strpos($env, 'APP_TIMEZONE=UTC') !== false) {
        $success[] = "✅ Timezone set to UTC";
    } else {
        $warnings[] = "⚠️  Timezone not set to UTC";
    }
} else {
    $errors[] = "❌ .env file not found";
}

// Check JWT keys
echo "Checking JWT keys...\n";
if (file_exists('storage/jwt_private.pem')) {
    $success[] = "✅ JWT private key file exists";
} else {
    $errors[] = "❌ JWT private key file not found";
}

if (file_exists('storage/jwt_public.pem')) {
    $success[] = "✅ JWT public key file exists";
} else {
    $errors[] = "❌ JWT public key file not found";
}

// Check config files
echo "Checking configuration files...\n";
if (file_exists('config/jwt.php')) {
    $success[] = "✅ JWT config file exists";
} else {
    $errors[] = "❌ JWT config file not found";
}

if (file_exists('config/livewire.php')) {
    $success[] = "✅ Livewire config file exists";
} else {
    $warnings[] = "⚠️  Livewire config file not found";
}

// Check required packages
echo "Checking required packages...\n";
if (file_exists('vendor/spatie/laravel-model-states')) {
    $success[] = "✅ spatie/laravel-model-states installed";
} else {
    $errors[] = "❌ spatie/laravel-model-states not installed";
}

if (file_exists('vendor/lcobucci/jwt')) {
    $success[] = "✅ lcobucci/jwt installed";
} else {
    $errors[] = "❌ lcobucci/jwt not installed";
}

if (file_exists('vendor/giorgiosironi/eris')) {
    $success[] = "✅ giorgiosironi/eris installed";
} else {
    $errors[] = "❌ giorgiosironi/eris not installed";
}

if (file_exists('vendor/livewire/livewire')) {
    $success[] = "✅ livewire/livewire installed";
} else {
    $errors[] = "❌ livewire/livewire not installed";
}

if (file_exists('vendor/predis/predis')) {
    $success[] = "✅ predis/predis installed";
} else {
    $errors[] = "❌ predis/predis not installed";
}

// Check documentation
echo "Checking documentation...\n";
if (file_exists('README_SETUP.md')) {
    $success[] = "✅ Setup documentation exists";
} else {
    $warnings[] = "⚠️  Setup documentation not found";
}

if (file_exists('QUEUE_SCHEDULER_GUIDE.md')) {
    $success[] = "✅ Queue/Scheduler guide exists";
} else {
    $warnings[] = "⚠️  Queue/Scheduler guide not found";
}

if (file_exists('TASK_1_COMPLETION.md')) {
    $success[] = "✅ Task 1 completion report exists";
} else {
    $warnings[] = "⚠️  Task 1 completion report not found";
}

// Check .gitignore
echo "Checking .gitignore...\n";
if (file_exists('.gitignore')) {
    $gitignore = file_get_contents('.gitignore');
    if (strpos($gitignore, 'jwt_private.pem') !== false || strpos($gitignore, '*.key') !== false) {
        $success[] = "✅ JWT private key in .gitignore";
    } else {
        $warnings[] = "⚠️  JWT private key not explicitly in .gitignore";
    }
}

// Print results
echo "\n===========================================\n";
echo "VERIFICATION RESULTS\n";
echo "===========================================\n\n";

if (!empty($success)) {
    echo "SUCCESS:\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "WARNINGS:\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

// Final verdict
echo "===========================================\n";
if (empty($errors)) {
    echo "✅ TASK 1 SETUP VERIFICATION PASSED\n";
    echo "===========================================\n\n";
    echo "All required components are installed and configured.\n";
    echo "You can proceed to Task 2 (Database Migrations).\n\n";
    echo "Next steps:\n";
    echo "1. Create MySQL database: license_platform\n";
    echo "2. Start Redis server\n";
    echo "3. Run migrations: php artisan migrate\n";
    exit(0);
} else {
    echo "❌ TASK 1 SETUP VERIFICATION FAILED\n";
    echo "===========================================\n\n";
    echo "Please fix the errors above before proceeding.\n";
    exit(1);
}
