<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Contracts\AuditLoggerInterface;
use App\Jobs\LogAuditEvent;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private AuditLoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new AuditLogger();
    }
#[Test]
    public function it_dispatches_log_audit_event_job()
    {
        Queue::fake();

        $this->logger->log(
            'ACTIVATION_SUCCESS',
            [
                'subject_type' => 'license',
                'subject_id' => 1,
                'ip_address' => '192.168.1.1',
                'license_key' => 'TEST-1234-5678-9012',
                'device_fp_hash' => hash('sha256', 'device-123'),
            ],
            'success',
            'info'
        );

        Queue::assertPushed(LogAuditEvent::class, function ($job) {
            return $job->eventType === 'ACTIVATION_SUCCESS'
                && $job->result === 'success'
                && $job->severity === 'info'
                && $job->payload['subject_type'] === 'license'
                && $job->payload['subject_id'] === 1;
        });
    }
#[Test]
    public function it_supports_all_event_types()
    {
        Queue::fake();

        $eventTypes = [
            'PRODUCT_CREATED',
            'PRODUCT_UPDATED',
            'PRODUCT_DELETED',
            'LICENSE_CREATED',
            'LICENSE_REVOKED',
            'LICENSE_SUSPENDED',
            'LICENSE_RESTORED',
            'LICENSE_RENEWED',
            'LICENSE_UNREVOKED',
            'ACTIVATION_SUCCESS',
            'ACTIVATION_FAILED',
            'VALIDATION_FAILED',
            'ADMIN_LOGIN',
            'ADMIN_LOGIN_FAILED',
            'ADMIN_LOCKED',
            'ACTIVATION_REVOKED',
        ];

        foreach ($eventTypes as $eventType) {
            $this->logger->log(
                $eventType,
                ['test' => 'data'],
                'success',
                'info'
            );
        }

        Queue::assertPushed(LogAuditEvent::class, count($eventTypes));
    }
#[Test]
    public function it_supports_different_severity_levels()
    {
        Queue::fake();

        $severities = ['info', 'warning', 'error'];

        foreach ($severities as $severity) {
            $this->logger->log(
                'TEST_EVENT',
                ['test' => 'data'],
                'success',
                $severity
            );
        }

        foreach ($severities as $severity) {
            Queue::assertPushed(LogAuditEvent::class, function ($job) use ($severity) {
                return $job->severity === $severity;
            });
        }
    }
#[Test]
    public function it_supports_different_result_types()
    {
        Queue::fake();

        $results = ['success', 'failure'];

        foreach ($results as $result) {
            $this->logger->log(
                'TEST_EVENT',
                ['test' => 'data'],
                $result,
                'info'
            );
        }

        foreach ($results as $result) {
            Queue::assertPushed(LogAuditEvent::class, function ($job) use ($result) {
                return $job->result === $result;
            });
        }
    }
}


