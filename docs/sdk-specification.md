# SDK Specification

## 1. Purpose

This document defines the public SDK contract for the License Platform. The SDK is the recommended integration layer for plugins, apps, and backend services that need to activate, validate, monitor, and meter licenses.

## 2. Supported Languages

| Language        | Package                      | Target                          | Phase   |
| --------------- | ---------------------------- | ------------------------------- | ------- |
| PHP             | `license-platform/php-sdk`   | WordPress plugins, Laravel apps | Phase 1 |
| JavaScript/Node | `@license-platform/node-sdk` | SaaS backend, Next.js           | Phase 2 |
| Python          | `license-platform-sdk`       | Django, FastAPI                 | Phase 3 |
| .NET            | `LicensePlatform.SDK`        | Desktop apps, .NET services     | Phase 3 |

## 3. PHP Client Interface

```php
interface LicensePlatformClientInterface {
    public function activate(string $licenseKey, string $domain, array $device): ActivationResult;
    public function validate(string $licenseKey, string $activationId, string $domain): ValidationResult;
    public function heartbeat(string $activationId, string $licenseKey, string $domain): HeartbeatResult;
    public function deactivate(string $activationId, string $licenseKey, string $reason): bool;
    public function checkUpdate(string $licenseKey, string $currentVersion, string $domain): UpdateResult;
    public function requestOfflineChallenge(string $licenseKey, string $domain, array $device): ChallengeResult;
    public function confirmOfflineActivation(string $challengeId, string $responseToken): ActivationResult;
    public function recordUsage(string $licenseKey, string $metricCode, int $quantity, string $idempotencyKey): UsageResult;
    public function validateCoupon(string $couponCode, string $planCode): CouponResult;
}
```

## 4. API Contract

The SDK must call the public API under `/v1/client` with JSON request/response bodies.

### Endpoints

- `POST /v1/client/licenses/activate`
- `POST /v1/client/licenses/validate`
- `POST /v1/client/licenses/heartbeat`
- `POST /v1/client/licenses/deactivate`
- `POST /v1/client/licenses/update-check`
- `POST /v1/client/licenses/offline/challenge`
- `POST /v1/client/licenses/offline/confirm`
- `POST /v1/client/usage/records`
- `POST /v1/client/coupons/validate`

### Common Headers

- `Authorization: Bearer <api_key>`
- `X-License-Platform-Product: <product_code>`
- `X-License-Platform-Environment: production|staging`
- `Idempotency-Key: <uuid>` for usage and activation flows when supported

## 5. Configuration

```php
$client = new LicensePlatformClient([
    'base_url' => getenv('LICENSE_PLATFORM_URL'),
    'api_key' => getenv('LICENSE_PLATFORM_KEY'),
    'product_code' => 'PLUGIN_SEO',
    'environment' => 'production',
    'timeout' => 10,
    'retry' => 2,
    'cache_driver' => 'file',
    'cache_path' => '/tmp/lp_cache',
    'cache_ttl' => 86400,
    'log_channel' => 'stderr',
]);
```

## 5. Versioning Policy

- SDKs must follow SemVer: `MAJOR.MINOR.PATCH`.
- MAJOR changes are reserved for breaking contract changes.
- MINOR changes add backward-compatible features.
- PATCH changes are for bug fixes only.
- SDKs should support at least two major API versions where possible.
- Deprecation notices should be issued at least 6 months before removal.

## 6. Error Hierarchy

- `LicensePlatformException`
    - `LicenseException`
        - `LicenseNotFoundException`
        - `LicenseExpiredException`
        - `LicenseRevokedException`
        - `LicenseSuspendedException`
    - `ActivationException`
        - `ActivationLimitExceededException`
        - `ActivationNotFoundException`
    - `NetworkException`
    - `AuthException`
    - `RateLimitException`

## 7. Changelog Policy

- Every SDK release must include a `CHANGELOG.md` using Keep a Changelog format.
- Deprecated methods must be marked with `@deprecated` and include replacement guidance.
- API responses should provide a `Sunset` header when an endpoint is scheduled for removal.
