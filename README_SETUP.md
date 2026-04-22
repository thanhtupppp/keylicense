# License Platform - Setup Documentation

## Project Initialization Complete

This Laravel project has been successfully initialized with all required dependencies and configurations for the License Platform system.

### Installed Components

#### Core Framework

- **Laravel 12.x** - Latest stable version
- **PHP 8.2.12** - Runtime environment

#### Required Packages

1. **spatie/laravel-model-states** (v2.12.1) - State machine implementation for license lifecycle
2. **lcobucci/jwt** (v4.0.4) - JWT token generation and verification for offline tokens
3. **giorgiosironi/eris** (v1.1.0) - Property-based testing framework
4. **livewire/livewire** (v4.2.4) - Real-time admin dashboard components
5. **predis/predis** (v3.4.2) - Redis client for caching and rate limiting

### Configuration

#### Environment Variables (.env)

The following configurations have been set up:

```env
APP_NAME="License Platform"
APP_TIMEZONE=UTC

# Database - MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=license_platform
DB_USERNAME=root
DB_PASSWORD=

# Queue - Redis
QUEUE_CONNECTION=redis

# Cache - Redis
CACHE_STORE=redis
CACHE_PREFIX=license_platform

# Redis Connection
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### JWT RSA Keypair

RSA-2048 keypair has been generated for JWT offline token signing:

- **Private Key**: Stored in `.env` as `JWT_PRIVATE_KEY` (never commit to source control)
- **Public Key**: Stored at `storage/jwt_public.pem` (used by SDK for offline verification)
- **Algorithm**: RS256 (RSA with SHA-256)

### Next Steps

#### 1. Database Setup

Create the MySQL database:

```bash
mysql -u root -p
CREATE DATABASE license_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

#### 2. Redis Setup

Ensure Redis server is running:

```bash
# Windows (if using Redis for Windows)
redis-server

# Or check if running
redis-cli ping
# Should return: PONG
```

#### 3. Run Migrations

Once database is created, run migrations:

```bash
php artisan migrate
```

#### 4. Queue Worker

Start the queue worker for background jobs (audit logging, etc.):

```bash
php artisan queue:work redis --tries=3
```

#### 5. Scheduler

Add to cron (Linux/Mac) or Task Scheduler (Windows):

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

For development, run:

```bash
php artisan schedule:work
```

#### 6. Development Server

Start the Laravel development server:

```bash
php artisan serve
```

### Security Notes

⚠️ **IMPORTANT**:

- The JWT private key in `.env` must NEVER be committed to version control
- Add `.env` to `.gitignore` (already configured by Laravel)
- The private key is used to sign offline tokens - if compromised, all tokens must be regenerated
- Public key at `storage/jwt_public.pem` can be safely distributed to SDK clients

### Project Structure

```
license-platform/
├── app/
│   ├── States/License/          # State machine classes (to be created)
│   ├── Services/                # Domain services (to be created)
│   ├── Http/Controllers/        # API and Admin controllers
│   └── Models/                  # Eloquent models
├── database/
│   └── migrations/              # Database migrations (to be created)
├── storage/
│   ├── jwt_private.pem          # JWT private key (DO NOT COMMIT)
│   └── jwt_public.pem           # JWT public key (safe to distribute)
├── tests/
│   ├── Feature/                 # Integration tests
│   └── Unit/                    # Unit and property-based tests
└── .env                         # Environment configuration (DO NOT COMMIT)
```

### Technical Decisions Implemented

✅ **T6**: JWT Offline Token with RS256 signing
✅ **T7**: Rate limiting configured to use Redis
✅ **T9**: Timezone set to UTC for all timestamps

### Requirements Addressed

This setup addresses the following requirements from the specification:

- **T6**: JWT RS256 keypair generated and configured
- **T7**: Redis configured for rate limiting
- **9.7**: HTTPS support (to be configured in production)

### Development Workflow

1. **State Machine** (Task 3): Implement license state classes using spatie/laravel-model-states
2. **Database** (Task 2): Create migrations for all tables
3. **Services** (Tasks 5-6): Implement domain services (LicenseService, ActivationService, etc.)
4. **API** (Task 8): Build REST API endpoints with middleware
5. **Admin Dashboard** (Tasks 9-11): Create Livewire components for admin interface
6. **Scheduler** (Task 13): Configure scheduled jobs for expiry checks and cleanup
7. **Testing** (Tasks throughout): Write property-based tests using Eris

### Troubleshooting

#### Redis Connection Issues

If you encounter Redis connection errors:

```bash
# Check if Redis is running
redis-cli ping

# If not installed, install Redis:
# Windows: https://github.com/microsoftarchive/redis/releases
# Linux: sudo apt-get install redis-server
# Mac: brew install redis
```

#### MySQL Connection Issues

Verify MySQL credentials in `.env` match your local setup:

```bash
mysql -u root -p
# Enter your password
```

#### Queue Not Processing

Ensure queue worker is running:

```bash
php artisan queue:work redis --tries=3 --timeout=90
```

---

**Setup completed successfully!** ✅

Next task: Implement database migrations (Task 2)
