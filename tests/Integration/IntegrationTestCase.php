<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Base class for integration tests that use real MySQL and Redis connections.
 * 
 * Unlike Feature tests that use SQLite in-memory and array cache,
 * Integration tests use actual MySQL database and Redis instance
 * to test real-world scenarios including concurrency and rate limiting.
 */
abstract class IntegrationTestCase extends TestCase
{

    /**
     * Setup the test environment with real MySQL and Redis.
     */
    protected function setUp(): void
    {
        // Use a dedicated SQLite file for integration tests before Laravel boots the app
        $databasePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'integration.sqlite';
        if (!file_exists($databasePath)) {
            file_put_contents($databasePath, '');
        }

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $databasePath;
        $_SERVER['DB_DATABASE'] = $databasePath;

        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => $databasePath]);

        // Configure Redis explicitly for integration tests.
        // Predis is available in this project and works consistently in CI/local environments.
        try {
            config(['database.redis.client' => 'predis']);
            config(['database.redis.default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => (int) env('REDIS_PORT', 6379),
                'database' => (int) env('REDIS_DB', 0),
            ]]);
            config(['cache.default' => 'redis']);
            config(['cache.prefix' => 'test_' . uniqid()]);

            // Test Redis connection
            Redis::connection()->ping();
        } catch (\Exception $exception) {
            // Fallback to array cache if Redis is not available
            config(['cache.default' => 'array']);
            $this->markTestSkipped('Redis is not available. Skipping integration test that requires Redis.');
        }

        // Run migrations on real MySQL database
        $this->artisan('migrate:fresh');
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        // Clear Redis test data
        try {
            Redis::flushdb();
        } catch (\Exception $exception) {
            // Ignore if Redis is not available
        }

        parent::tearDown();
    }
}
