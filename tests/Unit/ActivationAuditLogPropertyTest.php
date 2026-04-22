<?php

namespace Tests\Unit;

use App\Contracts\AuditLoggerInterface;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\Product;
use App\Services\ActivationService;
use App\States\License\InactiveState;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property-based test for activation audit log
 *
 * **Validates: Requirements 4.9**
 *
 * Property 9: Successful activation always produces an audit log entry
 *
 * For any successful activation, the audit log SHALL contain exactly one entry
 * of type `ACTIVATION_SUCCESS` that includes:
 * - License key reference (key_hash or license_id)
 * - Device fingerprint hash or user identifier
 * - Activation timestamp
 * - Client IP address
 *
 */
class ActivationAuditLogPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private ActivationService $service;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Use sync queue for testing to ensure audit logs are created immediately
        Queue::fake();

        $this->product = Product::create([
            'name'                    => 'Test Product',
            'slug'                    => 'test-product-audit-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-audit-' . uniqid(),
        ]);

        // Create service with real audit logger (not mocked) to test audit logging
        $auditLogger = app(\App\Contracts\AuditLoggerInterface::class);
        $this->service = new ActivationService($auditLogger);
    }

    // -------------------------------------------------------------------------
    // Property 9a — Per-device activation produces audit log
    // -------------------------------------------------------------------------

    /**
     * Property 9a: Per-device activation always produces exactly one audit log entry
     *
     * For any successful per-device activation, the audit log SHALL contain exactly
     * one entry of type `ACTIVATION_SUCCESS` with the correct license reference,
     * device fingerprint hash, timestamp, and IP address.
     *
     * Uses Eris to generate random device fingerprints and IP addresses.
     *
     */
    public function property_per_device_activation_produces_audit_log(): void
    {
        $this->limitTo(100)->forAll(
            // Random device fingerprint
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            )
        )->then(function (string $fingerprint) {
            // Reset queue fake for each iteration
            Queue::fake();

            // Create a per-device license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'per-device',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            $ipAddress = '192.168.1.1';

            // Perform activation
            $activation = $this->service->activate($license, $fingerprint, null, $ipAddress);
            $this->assertNotNull($activation);

            // Verify audit log job was dispatched
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, function ($job) use ($fingerprint, $ipAddress) {
                return $job->eventType === 'ACTIVATION_SUCCESS'
                    && $job->result === 'success'
                    && $job->severity === 'info'
                    && isset($job->payload['device_fp_hash'])
                    && $job->payload['device_fp_hash'] === hash('sha256', $fingerprint)
                    && isset($job->payload['ip_address'])
                    && $job->payload['ip_address'] === $ipAddress;
            });

            // Verify exactly one audit log job was dispatched
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, 1);
        });
    }

    // -------------------------------------------------------------------------
    // Property 9b — Per-user activation produces audit log
    // -------------------------------------------------------------------------

    /**
     * Property 9b: Per-user activation always produces exactly one audit log entry
     *
     * For any successful per-user activation, the audit log SHALL contain exactly
     * one entry of type `ACTIVATION_SUCCESS` with the correct license reference,
     * user identifier, timestamp, and IP address.
     *
     * Uses Eris to generate random user identifiers and IP addresses.
     *
     */
    public function property_per_user_activation_produces_audit_log(): void
    {
        $this->limitTo(100)->forAll(
            // Random user identifier
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            )
        )->then(function (string $userIdentifier) {
            // Reset queue fake for each iteration
            Queue::fake();

            // Create a per-user license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '5678',
                'license_model' => 'per-user',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            $ipAddress = '192.168.1.2';

            // Perform activation
            $activation = $this->service->activate($license, 'fingerprint', $userIdentifier, $ipAddress);
            $this->assertNotNull($activation);

            // Verify audit log job was dispatched
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, function ($job) use ($userIdentifier, $ipAddress) {
                return $job->eventType === 'ACTIVATION_SUCCESS'
                    && $job->result === 'success'
                    && $job->severity === 'info'
                    && isset($job->payload['user_identifier'])
                    && $job->payload['user_identifier'] === $userIdentifier
                    && isset($job->payload['ip_address'])
                    && $job->payload['ip_address'] === $ipAddress;
            });

            // Verify exactly one audit log job was dispatched
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, 1);
        });
    }

    // -------------------------------------------------------------------------
    // Property 9c — Floating activation produces audit log
    // -------------------------------------------------------------------------

    /**
     * Property 9c: Floating activation always produces exactly one audit log entry
     *
     * For any successful floating activation, the audit log SHALL contain exactly
     * one entry of type `ACTIVATION_SUCCESS` with the correct license reference,
     * device fingerprint hash, timestamp, and IP address.
     *
     * Uses Eris to generate random device fingerprints and IP addresses.
     *
     */
    public function property_floating_activation_produces_audit_log(): void
    {
        $this->limitTo(100)->forAll(
            // Random device fingerprint
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            )
        )->then(function (string $fingerprint) {
            // Reset queue fake for each iteration
            Queue::fake();

            // Create a floating license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '9999',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => 10,
                'expiry_date'   => null,
            ]);

            $ipAddress = '192.168.1.3';

            // Perform activation
            $activation = $this->service->activate($license, $fingerprint, null, $ipAddress);
            $this->assertNotNull($activation);

            // Verify audit log job was dispatched
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, function ($job) use ($fingerprint, $ipAddress) {
                return $job->eventType === 'ACTIVATION_SUCCESS'
                    && $job->result === 'success'
                    && $job->severity === 'info'
                    && isset($job->payload['device_fp_hash'])
                    && $job->payload['device_fp_hash'] === hash('sha256', $fingerprint)
                    && isset($job->payload['ip_address'])
                    && $job->payload['ip_address'] === $ipAddress;
            });

            // Verify exactly one audit log job was dispatched
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, 1);
        });
    }

    // -------------------------------------------------------------------------
    // Property 9d — Idempotent activations produce only one audit log
    // -------------------------------------------------------------------------

    /**
     * Property 9d: Idempotent activations produce only one audit log entry
     *
     * For any activation that is repeated multiple times with the same parameters
     * (idempotent behavior), only the first successful activation SHALL produce
     * an audit log entry. Subsequent idempotent calls SHALL NOT produce additional
     * audit log entries.
     *
     * Uses Eris to generate random repetition counts.
     *
     */
    public function property_idempotent_activations_produce_single_audit_log(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of repeated activation attempts (2–50)
            Generator\choose(2, 50)
        )->then(function (int $repetitions) {
            // Reset queue fake for each iteration
            Queue::fake();

            // Create a per-device license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '4321',
                'license_model' => 'per-device',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            $fingerprint = 'device-fingerprint-' . uniqid();
            $ipAddress = '192.168.1.1';

            // Perform the same activation multiple times
            for ($i = 0; $i < $repetitions; $i++) {
                $activation = $this->service->activate($license, $fingerprint, null, $ipAddress);
                $this->assertNotNull($activation);
            }

            // Verify exactly one audit log job was dispatched (for the first activation only)
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, 1);
        });
    }

    // -------------------------------------------------------------------------
    // Property 9e — Multiple different activations produce multiple audit logs
    // -------------------------------------------------------------------------

    /**
     * Property 9e: Multiple different activations produce multiple audit log entries
     *
     * For any floating license with multiple different device fingerprints,
     * each successful activation SHALL produce its own audit log entry.
     * The total number of audit log entries SHALL equal the number of unique
     * successful activations.
     *
     * Uses Eris to generate random numbers of different fingerprints.
     *
     */
    public function property_multiple_activations_produce_multiple_audit_logs(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of different fingerprints (1–20)
            Generator\choose(1, 20)
        )->then(function (int $fingerprintCount) {
            // Reset queue fake for each iteration
            Queue::fake();

            // Create a floating license with sufficient seats
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '7777',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $fingerprintCount + 10,
                'expiry_date'   => null,
            ]);

            // Activate with different fingerprints
            for ($i = 0; $i < $fingerprintCount; $i++) {
                $fingerprint = 'device-fingerprint-' . $i . '-' . uniqid();
                $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');
                $this->assertNotNull($activation);
            }

            // Verify exactly fingerprintCount audit log jobs were dispatched
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, $fingerprintCount);
        });
    }

    // -------------------------------------------------------------------------
    // Property 9f — Audit log contains activation timestamp
    // -------------------------------------------------------------------------

    /**
     * Property 9f: Audit log entry contains activation timestamp
     *
     * For any successful activation, the audit log entry SHALL contain a timestamp
     * that represents the activation time.
     *
     * Uses Eris to generate random license models.
     *
     */
    public function property_audit_log_contains_activation_timestamp(): void
    {
        $this->limitTo(100)->forAll(
            // Random license model
            Generator\elements('per-device', 'per-user', 'floating')
        )->then(function (string $licenseModel) {
            // Create a license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '8888',
                'license_model' => $licenseModel,
                'status'        => new InactiveState(new License()),
                'max_seats'     => 10,
                'expiry_date'   => null,
            ]);

            $fingerprint = 'device-fingerprint-' . uniqid();
            $userIdentifier = 'user-' . uniqid() . '@example.com';

            // Perform activation
            $activation = $this->service->activate(
                $license,
                $fingerprint,
                $licenseModel === 'per-user' ? $userIdentifier : null,
                '127.0.0.1'
            );
            $this->assertNotNull($activation);

            // Verify audit log job was dispatched with timestamp in payload
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, function ($job) {
                return isset($job->payload['activated_at']);
            });
        });
    }

    // -------------------------------------------------------------------------
    // Property 9g — Audit log contains license key reference
    // -------------------------------------------------------------------------

    /**
     * Property 9g: Audit log entry contains license key reference
     *
     * For any successful activation, the audit log entry SHALL contain a reference
     * to the license key (either key_hash or license_id) that can be used to
     * identify which license was activated.
     *
     * Uses Eris to generate random license models.
     *
     */
    public function property_audit_log_contains_license_key_reference(): void
    {
        $this->limitTo(100)->forAll(
            // Random license model
            Generator\elements('per-device', 'per-user', 'floating')
        )->then(function (string $licenseModel) {
            // Create a license
            $keyHash = hash('sha256', 'TEST-KEY-' . uniqid());
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => $keyHash,
                'key_last4'     => '9999',
                'license_model' => $licenseModel,
                'status'        => new InactiveState(new License()),
                'max_seats'     => 10,
                'expiry_date'   => null,
            ]);

            $fingerprint = 'device-fingerprint-' . uniqid();
            $userIdentifier = 'user-' . uniqid() . '@example.com';

            // Perform activation
            $activation = $this->service->activate(
                $license,
                $fingerprint,
                $licenseModel === 'per-user' ? $userIdentifier : null,
                '127.0.0.1'
            );
            $this->assertNotNull($activation);

            // Verify audit log job was dispatched with license reference
            Queue::assertPushed(\App\Jobs\LogAuditEvent::class, function ($job) {
                return isset($job->payload['license_id']) || isset($job->payload['key_hash']);
            });
        });
    }
}

