<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Jobs\LogAuditEvent;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogAuditEventJobTest extends TestCase
{
    use RefreshDatabase;
#[Test]
    public function it_creates_audit_log_record()
    {
        $job = new LogAuditEvent(
            'ACTIVATION_SUCCESS',
            [
                'subject_type' => 'license',
                'subject_id' => 123,
                'ip_address' => '192.168.1.1',
                'license_key' => 'TEST-1234-5678-9012',
                'device_fp_hash' => hash('sha256', 'device-123'),
            ],
            'success',
            'info'
        );

        $job->handle();

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'ACTIVATION_SUCCESS',
            'subject_type' => 'license',
            'subject_id' => 123,
            'ip_address' => '192.168.1.1',
            'result' => 'success',
            'severity' => 'info',
        ]);

        $log = AuditLog::first();
        $this->assertEquals('ACTIVATION_SUCCESS', $log->event_type);
        $this->assertEquals('license', $log->subject_type);
        $this->assertEquals(123, $log->subject_id);
        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('success', $log->result);
        $this->assertEquals('info', $log->severity);
        $this->assertIsArray($log->payload);
        $this->assertEquals('TEST-1234-5678-9012', $log->payload['license_key']);
    }
#[Test]
    public function it_handles_nullable_fields()
    {
        $job = new LogAuditEvent(
            'ADMIN_LOGIN',
            [
                'username' => 'admin',
            ],
            'success',
            'info'
        );

        $job->handle();

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'ADMIN_LOGIN',
            'subject_type' => null,
            'subject_id' => null,
            'ip_address' => null,
            'result' => 'success',
            'severity' => 'info',
        ]);

        $log = AuditLog::first();
        $this->assertNull($log->subject_type);
        $this->assertNull($log->subject_id);
        $this->assertNull($log->ip_address);
        $this->assertEquals('admin', $log->payload['username']);
    }
#[Test]
    public function it_stores_complex_payload_as_json()
    {
        $complexPayload = [
            'subject_type' => 'license',
            'subject_id' => 456,
            'ip_address' => '10.0.0.1',
            'nested' => [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            'array_data' => [1, 2, 3, 4, 5],
        ];

        $job = new LogAuditEvent(
            'LICENSE_REVOKED',
            $complexPayload,
            'success',
            'warning'
        );

        $job->handle();

        $log = AuditLog::first();
        $this->assertIsArray($log->payload);
        $this->assertEquals($complexPayload, $log->payload);
        $this->assertEquals('value1', $log->payload['nested']['key1']);
        $this->assertEquals([1, 2, 3, 4, 5], $log->payload['array_data']);
    }
#[Test]
    public function it_sets_created_at_timestamp()
    {
        $job = new LogAuditEvent(
            'TEST_EVENT',
            ['test' => 'data'],
            'success',
            'info'
        );

        $job->handle();

        $log = AuditLog::first();
        $this->assertNotNull($log->created_at);
        $this->assertTrue($log->created_at->diffInSeconds(now()) < 2);
    }
}


