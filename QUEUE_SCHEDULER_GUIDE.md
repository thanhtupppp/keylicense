# Queue Worker and Scheduler Configuration Guide

## Overview

The License Platform uses Laravel's queue system for background job processing (audit logging, notifications) and scheduler for periodic tasks (expiry checks, heartbeat cleanup, audit log archiving).

## Queue Configuration

### Queue Driver: Redis

The queue is configured to use Redis for better performance and reliability in multi-instance environments.

**Configuration in `.env`:**

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Starting the Queue Worker

#### Development

```bash
php artisan queue:work redis --tries=3 --timeout=90
```

#### Production (with Supervisor)

Create a supervisor configuration file: `/etc/supervisor/conf.d/license-platform-worker.conf`

```ini
[program:license-platform-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/license-platform/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/license-platform/storage/logs/worker.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start license-platform-worker:*
```

### Queue Jobs

The following jobs are queued for background processing:

1. **LogAuditEvent** - Logs audit events asynchronously
    - Queue: `default`
    - Tries: 3
    - Timeout: 60 seconds

2. **SendLicenseNotification** (future) - Sends email notifications
    - Queue: `notifications`
    - Tries: 3
    - Timeout: 120 seconds

### Monitoring Queue

```bash
# Check queue status
php artisan queue:monitor redis:default,redis:notifications --max=100

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Scheduler Configuration

### Scheduled Tasks

The following tasks are scheduled to run automatically:

| Task              | Schedule           | Command                       | Purpose                                                  |
| ----------------- | ------------------ | ----------------------------- | -------------------------------------------------------- |
| Expiry Check      | Daily at 00:00 UTC | `license:check-expiry`        | Automatically expire licenses past their expiry_date     |
| Heartbeat Cleanup | Every minute       | `license:cleanup-stale-seats` | Release floating seats without heartbeat for >10 minutes |
| Audit Log Archive | Daily at 02:00 UTC | `audit:archive-old-logs`      | Archive audit logs older than 365 days                   |

### Scheduler Configuration

The scheduler is configured in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Expiry check - daily at midnight UTC
    $schedule->command('license:check-expiry')
        ->daily()
        ->timezone('UTC')
        ->withoutOverlapping()
        ->onOneServer();

    // Heartbeat cleanup - every minute
    $schedule->command('license:cleanup-stale-seats')
        ->everyMinute()
        ->withoutOverlapping()
        ->onOneServer();

    // Audit log archive - daily at 2 AM UTC
    $schedule->command('audit:archive-old-logs')
        ->dailyAt('02:00')
        ->timezone('UTC')
        ->withoutOverlapping()
        ->onOneServer();
}
```

### Running the Scheduler

#### Development

```bash
php artisan schedule:work
```

This command runs the scheduler in the foreground, checking for due tasks every minute.

#### Production (Cron)

Add this single cron entry to run the scheduler:

```bash
* * * * * cd /path/to/license-platform && php artisan schedule:run >> /dev/null 2>&1
```

**To add to crontab:**

```bash
crontab -e
```

Then add the line above.

#### Production (Windows Task Scheduler)

1. Open Task Scheduler
2. Create Basic Task
3. Name: "License Platform Scheduler"
4. Trigger: Daily, repeat every 1 minute
5. Action: Start a program
    - Program: `C:\path\to\php.exe`
    - Arguments: `artisan schedule:run`
    - Start in: `C:\path\to\license-platform`

### Scheduler Features

#### Without Overlapping

Prevents the same task from running concurrently:

```php
->withoutOverlapping()
```

#### On One Server

In multi-server environments, ensures task runs on only one server:

```php
->onOneServer()
```

This requires a cache driver that supports locks (Redis, Memcached, DynamoDB).

#### Timezone

All scheduled tasks use UTC timezone:

```php
->timezone('UTC')
```

### Monitoring Scheduler

```bash
# List all scheduled tasks
php artisan schedule:list

# Test scheduler (runs all due tasks immediately)
php artisan schedule:test

# Run scheduler once (useful for testing)
php artisan schedule:run
```

## Redis Configuration

### Redis Connection

The application uses Redis for:

1. **Queue** - Background job processing
2. **Cache** - Application caching
3. **Rate Limiting** - API rate limiting by X-API-Key

**Configuration in `config/database.php`:**

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,
    ],

    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 1,
    ],

    'queue' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 2,
    ],
],
```

### Redis Installation

#### Windows

Download from: https://github.com/microsoftarchive/redis/releases

Or use WSL:

```bash
wsl --install
wsl
sudo apt-get update
sudo apt-get install redis-server
redis-server
```

#### Linux (Ubuntu/Debian)

```bash
sudo apt-get update
sudo apt-get install redis-server
sudo systemctl start redis
sudo systemctl enable redis
```

#### macOS

```bash
brew install redis
brew services start redis
```

### Redis Monitoring

```bash
# Check if Redis is running
redis-cli ping
# Should return: PONG

# Monitor Redis commands in real-time
redis-cli monitor

# Get Redis info
redis-cli info

# Check memory usage
redis-cli info memory

# List all keys (use with caution in production)
redis-cli keys "*"

# Check queue length
redis-cli llen "queues:default"
```

## Troubleshooting

### Queue Worker Not Processing Jobs

1. **Check if worker is running:**

    ```bash
    ps aux | grep "queue:work"
    ```

2. **Check Redis connection:**

    ```bash
    redis-cli ping
    ```

3. **Check failed jobs:**

    ```bash
    php artisan queue:failed
    ```

4. **Restart worker:**
    ```bash
    php artisan queue:restart
    ```

### Scheduler Not Running

1. **Check cron is configured:**

    ```bash
    crontab -l
    ```

2. **Test scheduler manually:**

    ```bash
    php artisan schedule:run
    ```

3. **Check scheduler log:**

    ```bash
    tail -f storage/logs/laravel.log
    ```

4. **Verify timezone:**
    ```bash
    php artisan schedule:list
    ```

### Redis Connection Issues

1. **Check Redis is running:**

    ```bash
    redis-cli ping
    ```

2. **Check Redis configuration in `.env`:**

    ```env
    REDIS_HOST=127.0.0.1
    REDIS_PORT=6379
    REDIS_PASSWORD=null
    ```

3. **Test Redis connection:**
    ```bash
    php artisan tinker
    >>> Redis::connection()->ping();
    ```

## Performance Optimization

### Queue Workers

- **Multiple workers**: Run multiple queue workers for better throughput
- **Separate queues**: Use different queues for different job types
- **Memory limits**: Set `--memory=512` to restart workers after memory threshold

```bash
php artisan queue:work redis --queue=high,default,low --memory=512 --tries=3
```

### Scheduler

- **Mutex**: Use Redis for scheduler mutex in multi-server environments
- **Logging**: Enable scheduler logging for debugging
- **Monitoring**: Use Laravel Horizon for advanced queue monitoring (optional)

## Production Checklist

- [ ] Redis is installed and running
- [ ] Queue worker is configured with Supervisor
- [ ] Cron job is configured for scheduler
- [ ] Failed job monitoring is set up
- [ ] Log rotation is configured
- [ ] Redis persistence is enabled (RDB or AOF)
- [ ] Redis memory limit is configured
- [ ] Queue worker auto-restart is configured
- [ ] Scheduler timezone is set to UTC
- [ ] Rate limiting is tested with Redis

---

**Configuration completed successfully!** ✅

For more information, see:

- [Laravel Queues Documentation](https://laravel.com/docs/queues)
- [Laravel Task Scheduling Documentation](https://laravel.com/docs/scheduling)
- [Redis Documentation](https://redis.io/documentation)
