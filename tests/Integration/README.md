# Integration Tests

This directory contains integration tests that use **real MySQL and Redis connections** (not in-memory databases) to test end-to-end flows and real-world scenarios.

## Differences from Feature Tests

| Aspect        | Feature Tests        | Integration Tests    |
| ------------- | -------------------- | -------------------- |
| Database      | SQLite in-memory     | Real MySQL           |
| Cache         | Array driver         | Real Redis           |
| Purpose       | Fast unit-like tests | Real-world scenarios |
| Concurrency   | Not tested           | Tested with real DB  |
| Rate Limiting | Mocked               | Real Redis counters  |

## Prerequisites

Before running integration tests, ensure you have:

1. **MySQL** running locally on port 3306
2. **Redis** running locally on port 6379
3. A test database created: `license_platform_test`

### Setup MySQL Test Database

```bash
mysql -u root -p
CREATE DATABASE license_platform_test;
EXIT;
```

### Verify Redis is Running

```bash
redis-cli ping
# Should return: PONG
```

## Running Integration Tests

### Run All Integration Tests

```bash
php artisan test --testsuite=Integration
```

Or using the dedicated configuration file:

```bash
vendor/bin/phpunit --configuration phpunit.integration.xml
```

### Run Specific Test Files

```bash
# Full activation flow tests
php artisan test tests/Integration/FullActivationFlowTest.php

# Concurrent floating seat tests
php artisan test tests/Integration/ConcurrentFloatingSeatTest.php

# Rate limiting tests
php artisan test tests/Integration/RateLimitingTest.php

# Scheduler jobs tests
php artisan test tests/Integration/SchedulerJobsTest.php
```

### Run Specific Test Methods

```bash
php artisan test --filter=test_full_per_device_activation_flow_end_to_end
```

## Test Coverage

### 14.1 Full Activation Flow Tests (`FullActivationFlowTest.php`)

Tests end-to-end activation flow from API request to database records to offline token response:

- ✅ Per-device activation flow
- ✅ Per-user activation flow
- ✅ Floating license activation flow
- ✅ Idempotent activation (no duplicate records)
- ✅ Expired license rejection
- ✅ Inactive product rejection

**Requirements:** 4.1, 4.2, 4.4, 4.6, 9.8

### 14.2 Concurrent Floating Seat Tests (`ConcurrentFloatingSeatTest.php`)

Tests concurrent requests to verify no race conditions occur:

- ✅ Concurrent activations respect seat limit
- ✅ Concurrent activations with same device are idempotent
- ✅ Seat release allows immediate new activation
- ✅ Concurrent heartbeats update timestamps correctly
- ✅ Database unique constraint prevents duplicate seats

**Requirements:** T10, 4.7, 9.9

### 14.3 Rate Limiting Tests (`RateLimitingTest.php`)

Tests rate limiting with actual Redis instance:

- ✅ Enforces 60 requests per minute per API key
- ✅ Rate limiting is independent per API key
- ✅ Rate limit resets after time window
- ✅ Rate limiting applies to all endpoints except public key
- ✅ Redis stores correct counter values
- ✅ Rate limiting shared across different endpoints

**Requirements:** T7, 9.5, 9.6

### 14.4 Scheduler Jobs Tests (`SchedulerJobsTest.php`)

Tests automated scheduler jobs work correctly:

- ✅ Expiry check job expires licenses past expiry date
- ✅ Expiry check job only affects active licenses
- ✅ Heartbeat cleanup job removes stale seats (>10 minutes)
- ✅ Heartbeat cleanup respects 10-minute threshold
- ✅ Audit log archive job removes old logs (>365 days)
- ✅ All scheduler jobs run without errors

**Requirements:** 3.5, 7.3

## Important Notes

### Database State

Integration tests use `RefreshDatabase` trait, which:

- Runs migrations before each test
- Rolls back transactions after each test
- Ensures clean state for each test

### Redis Cleanup

The `IntegrationTestCase` base class automatically:

- Uses a unique cache prefix per test run
- Flushes Redis after each test
- Prevents test data pollution

### Concurrency Testing

Concurrent tests simulate real-world race conditions by:

- Using real database transactions
- Testing unique constraint violations
- Verifying seat allocation limits

### Performance

Integration tests are slower than feature tests because they:

- Use real MySQL (not in-memory SQLite)
- Use real Redis (not array cache)
- Test actual concurrency scenarios

Expect integration tests to take 2-5x longer than feature tests.

## Troubleshooting

### MySQL Connection Error

```
SQLSTATE[HY000] [2002] Connection refused
```

**Solution:** Ensure MySQL is running and accessible:

```bash
mysql -u root -p -e "SELECT 1"
```

### Redis Connection Error

```
Connection refused [tcp://127.0.0.1:6379]
```

**Solution:** Ensure Redis is running:

```bash
redis-cli ping
```

### Database Permission Error

```
SQLSTATE[42000]: Access denied for user 'root'@'localhost'
```

**Solution:** Update `phpunit.integration.xml` with correct MySQL credentials.

### Rate Limit Tests Failing

If rate limit tests fail intermittently, ensure:

1. Redis is not being used by other processes
2. No other tests are running concurrently
3. Redis cache is cleared before tests

## CI/CD Integration

For CI/CD pipelines, ensure:

1. MySQL service is available
2. Redis service is available
3. Test database is created before running tests

Example GitHub Actions:

```yaml
services:
    mysql:
        image: mysql:8.0
        env:
            MYSQL_ROOT_PASSWORD: password
            MYSQL_DATABASE: license_platform_test
        ports:
            - 3306:3306

    redis:
        image: redis:7
        ports:
            - 6379:6379

steps:
    - name: Run Integration Tests
      run: vendor/bin/phpunit --configuration phpunit.integration.xml
```

## Best Practices

1. **Run integration tests separately** from unit/feature tests
2. **Use dedicated test database** (never use production database)
3. **Clean up Redis** between test runs if needed
4. **Monitor test execution time** - integration tests should complete in <5 minutes
5. **Run locally before pushing** to catch issues early

## Related Documentation

- [Design Document](../../.kiro/specs/license-platform/design.md)
- [Requirements](../../.kiro/specs/license-platform/requirements.md)
- [Tasks](../../.kiro/specs/license-platform/tasks.md)
