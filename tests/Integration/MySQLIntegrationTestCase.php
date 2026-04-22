<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Base class for integration tests that use real MySQL (but not Redis).
 * 
 * Use this for tests that need real database but don't require Redis.
 * For tests that need both MySQL and Redis, use IntegrationTestCase.
 */
abstract class MySQLIntegrationTestCase extends TestCase
{

    /**
     * Setup the test environment with real MySQL.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']); // Use array cache (no Redis required)

        // Run migrations on the integration SQLite database
        $this->artisan('migrate:fresh');
    }
}
