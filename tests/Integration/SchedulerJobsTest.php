<?php

namespace Tests\Integration;

use App\Console\Commands\ArchiveAuditLogs;
use App\Console\Commands\CheckExpiredLicenses;
use App\Console\Commands\CleanupStaleHeartbeats;
use App\Models\AuditLog;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\Product;
use App\States\License\ActiveState;
use App\States\License\InactiveState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * Integration test for scheduler jobs.
 * Tests automated tasks work correctly with real database.
 * 
 * Requirements: 3.5, 7.3
 */
class SchedulerJobsTest extends MySQLIntegrationTestCase
{
    /**
     * Test expiry check job transitions expired licenses to expired status.
     * 
     */
    public function test_expiry_check_job_expires_licenses_past_expiry_date(): void
    {
        // Arrange: Create licenses with different expiry dates
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        // License expired 10 days ago
        $expiredLicense1 = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'EXPIRED-1'),
            'key_last4' => 'EXP1',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
            'expiry_date' => Carbon::now()->subDays(10),
        ]);

        // License expired yesterday
        $expiredLicense2 = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'EXPIRED-2'),
            'key_last4' => 'EXP2',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
            'expiry_date' => Carbon::now()->subDay(),
        ]);

        // License expires tomorrow (should NOT be expired)
        $validLicense = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'VALID-1'),
            'key_last4' => 'VAL1',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
            'expiry_date' => Carbon::now()->addDay(),
        ]);

        // License with no expiry date (should NOT be expired)
        $perpetualLicense = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'PERPETUAL-1'),
            'key_last4' => 'PER1',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
            'expiry_date' => null,
        ]);

        // Act: Run expiry check command
        $exitCode = Artisan::call(CheckExpiredLicenses::class);

        // Assert: Command executed successfully
        $this->assertEquals(0, $exitCode);

        // Assert: Expired licenses are now in expired status
        $expiredLicense1->refresh();
        $this->assertEquals('expired', $expiredLicense1->status->getValue());

        $expiredLicense2->refresh();
        $this->assertEquals('expired', $expiredLicense2->status->getValue());

        // Assert: Valid licenses remain active
        $validLicense->refresh();
        $this->assertEquals('active', $validLicense->status->getValue());

        $perpetualLicense->refresh();
        $this->assertEquals('active', $perpetualLicense->status->getValue());
    }

    /**
     * Test expiry check job only affects active licenses.
     * 
     */
    public function test_expiry_check_job_only_affects_active_licenses(): void
    {
        // Arrange: Create licenses in different states with past expiry dates
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $pastDate = Carbon::now()->subDays(10);

        // Active license with past expiry (should be expired)
        $activeLicense = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'ACTIVE-EXPIRED'),
            'key_last4' => 'ACT1',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
            'expiry_date' => $pastDate,
        ]);

        // Inactive license with past expiry (should remain inactive)
        $inactiveLicense = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'INACTIVE-EXPIRED'),
            'key_last4' => 'INA1',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
            'expiry_date' => $pastDate,
        ]);

        // Suspended license with past expiry (should remain suspended)
        $suspendedLicense = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'SUSPENDED-EXPIRED'),
            'key_last4' => 'SUS1',
            'license_model' => 'per-device',
            'status' => 'suspended',
            'expiry_date' => $pastDate,
        ]);

        // Revoked license with past expiry (should remain revoked)
        $revokedLicense = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'REVOKED-EXPIRED'),
            'key_last4' => 'REV1',
            'license_model' => 'per-device',
            'status' => 'revoked',
            'expiry_date' => $pastDate,
        ]);

        // Act: Run expiry check command
        Artisan::call(CheckExpiredLicenses::class);

        // Assert: Only active license transitioned to expired
        $activeLicense->refresh();
        $this->assertEquals('expired', $activeLicense->status->getValue());

        // Assert: Other licenses remain in their original states
        $inactiveLicense->refresh();
        $this->assertEquals('inactive', $inactiveLicense->status->getValue());

        $suspendedLicense->refresh();
        $this->assertEquals('suspended', $suspendedLicense->status->getValue());

        $revokedLicense->refresh();
        $this->assertEquals('revoked', $revokedLicense->status->getValue());
    }

    /**
     * Test heartbeat cleanup job removes stale floating seats.
     * 
     */
    public function test_heartbeat_cleanup_job_removes_stale_seats(): void
    {
        // Arrange: Create floating license with seats
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'FLOAT-CLEANUP'),
            'key_last4' => 'CLN1',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => new ActiveState(new License()),
        ]);

        // Create floating seats with different heartbeat timestamps
        $staleSeat1 = FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'stale-device-1'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(15), // Stale (>10 min)
        ]);

        $staleSeat2 = FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'stale-device-2'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(11), // Stale (>10 min)
        ]);

        $activeSeat1 = FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'active-device-1'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(5), // Active (<10 min)
        ]);

        $activeSeat2 = FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'active-device-2'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(9), // Active (<10 min)
        ]);

        // Act: Run heartbeat cleanup command
        $exitCode = Artisan::call(CleanupStaleHeartbeats::class);

        // Assert: Command executed successfully
        $this->assertEquals(0, $exitCode);

        // Assert: Stale seats are removed
        $this->assertDatabaseMissing('floating_seats', [
            'id' => $staleSeat1->id,
        ]);

        $this->assertDatabaseMissing('floating_seats', [
            'id' => $staleSeat2->id,
        ]);

        // Assert: Active seats remain
        $this->assertDatabaseHas('floating_seats', [
            'id' => $activeSeat1->id,
        ]);

        $this->assertDatabaseHas('floating_seats', [
            'id' => $activeSeat2->id,
        ]);

        // Assert: Exactly 2 seats remain
        $remainingSeats = FloatingSeat::where('license_id', $license->id)->count();
        $this->assertEquals(2, $remainingSeats);
    }

    /**
     * Test heartbeat cleanup job with exactly 10 minute threshold.
     * 
     */
    public function test_heartbeat_cleanup_respects_10_minute_threshold(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'FLOAT-THRESHOLD'),
            'key_last4' => 'THR1',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => new ActiveState(new License()),
        ]);

        // Seat at exactly 10 minutes (should remain because cleanup removes only stale seats older than 10 minutes)
        $exactlyTenMinutes = FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'exactly-10-min'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(10),
        ]);

        // Seat at 9 minutes 59 seconds (should remain)
        $justUnderTen = FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'just-under-10'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(9)->subSeconds(59),
        ]);

        // Seat at 10 minutes 1 second (should be removed)
        $justOverTen = FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'just-over-10'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(10)->subSecond(),
        ]);

        // Act: Run cleanup
        Artisan::call(CleanupStaleHeartbeats::class);

        // Assert: Only seats older than 10 minutes are removed
        $this->assertDatabaseHas('floating_seats', [
            'id' => $exactlyTenMinutes->id,
        ]);

        $this->assertDatabaseHas('floating_seats', [
            'id' => $justUnderTen->id,
        ]);

        $this->assertDatabaseMissing('floating_seats', [
            'id' => $justOverTen->id,
        ]);

        // Assert: Seat < 10 minutes remains
        $this->assertDatabaseHas('floating_seats', [
            'id' => $justUnderTen->id,
        ]);
    }

    /**
     * Test audit log archive job removes old audit logs.
     * 
     */
    public function test_audit_log_archive_job_removes_old_logs(): void
    {
        // Arrange: Create audit logs with different ages
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        // Old log (>365 days, should be archived/deleted)
        $oldLog1 = AuditLog::create([
            'event_type' => 'ACTIVATION_SUCCESS',
            'subject_type' => 'license',
            'subject_id' => 1,
            'ip_address' => '192.168.1.1',
            'payload' => json_encode(['test' => 'data']),
            'result' => 'success',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDays(400),
        ]);

        $oldLog2 = AuditLog::create([
            'event_type' => 'LICENSE_CREATED',
            'subject_type' => 'license',
            'subject_id' => 2,
            'ip_address' => '192.168.1.2',
            'payload' => json_encode(['test' => 'data']),
            'result' => 'success',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDays(366),
        ]);

        // Recent log (<365 days, should remain)
        $recentLog = AuditLog::create([
            'event_type' => 'ACTIVATION_SUCCESS',
            'subject_type' => 'license',
            'subject_id' => 3,
            'ip_address' => '192.168.1.3',
            'payload' => json_encode(['test' => 'data']),
            'result' => 'success',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDays(300),
        ]);

        // Very recent log (should remain)
        $veryRecentLog = AuditLog::create([
            'event_type' => 'ACTIVATION_SUCCESS',
            'subject_type' => 'license',
            'subject_id' => 4,
            'ip_address' => '192.168.1.4',
            'payload' => json_encode(['test' => 'data']),
            'result' => 'success',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Act: Run archive command in non-interactive mode
        $exitCode = Artisan::call(ArchiveAuditLogs::class, ['--no-interaction' => true]);

        // Assert: Command executed successfully
        $this->assertEquals(0, $exitCode);

        // Assert: Old logs are removed
        $this->assertDatabaseMissing('audit_logs', [
            'id' => $oldLog1->id,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'id' => $oldLog2->id,
        ]);

        // Assert: Recent logs remain
        $this->assertDatabaseHas('audit_logs', [
            'id' => $recentLog->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $veryRecentLog->id,
        ]);
    }

    /**
     * Test all scheduler jobs can run without errors.
     * 
     */
    public function test_all_scheduler_jobs_run_without_errors(): void
    {
        // Arrange: Create some test data
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => hash('sha256', 'TEST-ALL-JOBS'),
            'key_last4' => 'JOBS',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => new ActiveState(new License()),
            'expiry_date' => Carbon::now()->subDays(10),
        ]);

        FloatingSeat::factory()->create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'test-device'),
            'last_heartbeat_at' => Carbon::now()->subMinutes(15),
        ]);

        AuditLog::create([
            'event_type' => 'TEST_EVENT',
            'subject_type' => 'license',
            'subject_id' => $license->id,
            'ip_address' => '192.168.1.1',
            'payload' => json_encode(['test' => 'data']),
            'result' => 'success',
            'severity' => 'info',
            'created_at' => Carbon::now()->subDays(400),
        ]);

        // Act & Assert: Run all scheduler jobs
        $exitCode1 = Artisan::call(CheckExpiredLicenses::class);
        $this->assertEquals(0, $exitCode1, 'CheckExpiredLicenses should complete successfully');

        $exitCode2 = Artisan::call(CleanupStaleHeartbeats::class);
        $this->assertEquals(0, $exitCode2, 'CleanupStaleHeartbeats should complete successfully');

        $exitCode3 = Artisan::call(ArchiveAuditLogs::class, ['--no-interaction' => true]);
        $this->assertEquals(0, $exitCode3, 'ArchiveAuditLogs should complete successfully');

        // Assert: Jobs performed their tasks
        $license->refresh();
        $this->assertEquals('expired', $license->status->getValue(), 'License should be expired');

        $seatCount = FloatingSeat::where('license_id', $license->id)->count();
        $this->assertEquals(0, $seatCount, 'Stale seat should be removed');

        $oldLogCount = AuditLog::where('created_at', '<', Carbon::now()->subDays(365))->count();
        $this->assertEquals(0, $oldLogCount, 'Old audit logs should be archived');
    }
}

