<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\License;
use App\Models\FloatingSeat;
use App\Models\AuditLog;
use Carbon\Carbon;

echo "Creating test data...\n";

$uniqueId = uniqid();

// Create a test product
$product = Product::create([
    'name' => 'Test Product ' . $uniqueId,
    'slug' => 'test-product-' . $uniqueId,
    'api_key' => 'test-api-key-' . $uniqueId,
    'status' => 'active',
    'offline_token_ttl_hours' => 24
]);

echo "Created product: {$product->name} (ID: {$product->id})\n";

// Create an active license with expired date
$license = License::create([
    'product_id' => $product->id,
    'key_hash' => hash('sha256', 'TEST-EXPIRED-LICENSE-KEY-' . $uniqueId),
    'key_last4' => 'XPRD',
    'license_model' => 'per-device',
    'status' => 'active',
    'expiry_date' => Carbon::yesterday()
]);

echo "Created expired license: {$license->key_last4} (ID: {$license->id}, expires: {$license->expiry_date})\n";

// Create a stale floating seat
$staleLicense = License::create([
    'product_id' => $product->id,
    'key_hash' => hash('sha256', 'TEST-FLOATING-LICENSE-KEY-' . $uniqueId),
    'key_last4' => 'STAL',
    'license_model' => 'floating',
    'status' => 'active',
    'max_seats' => 5
]);

$activation = \App\Models\Activation::create([
    'license_id' => $staleLicense->id,
    'device_fp_hash' => hash('sha256', 'test-device-fingerprint-' . $uniqueId),
    'type' => 'floating',
    'activated_at' => now(),
    'is_active' => true
]);

$staleSeat = FloatingSeat::create([
    'license_id' => $staleLicense->id,
    'activation_id' => $activation->id,
    'device_fp_hash' => hash('sha256', 'test-device-fingerprint-' . $uniqueId),
    'last_heartbeat_at' => Carbon::now()->subMinutes(15) // 15 minutes ago (stale)
]);

echo "Created stale floating seat (ID: {$staleSeat->id}, last heartbeat: {$staleSeat->last_heartbeat_at})\n";

// Create old audit logs
$oldAuditLog = AuditLog::create([
    'event_type' => 'TEST_EVENT',
    'subject_type' => 'license',
    'subject_id' => $license->id,
    'ip_address' => '127.0.0.1',
    'payload' => ['test' => 'data'],
    'result' => 'success',
    'severity' => 'info',
    'created_at' => Carbon::now()->subDays(400) // 400 days ago
]);

echo "Created old audit log (ID: {$oldAuditLog->id}, created: {$oldAuditLog->created_at})\n";

echo "\nTest data created successfully!\n";
echo "Now run the scheduler commands to test them:\n";
echo "- php artisan licenses:check-expired\n";
echo "- php artisan heartbeats:cleanup\n";
echo "- php artisan audit-logs:archive --dry-run\n";
