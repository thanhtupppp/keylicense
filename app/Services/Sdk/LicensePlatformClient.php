<?php

namespace App\Services\Sdk;

use App\Services\Sdk\Contracts\LicensePlatformClientInterface;
use App\Services\Sdk\Dto\ActivationResult;
use App\Services\Sdk\Dto\ChallengeResult;
use App\Services\Sdk\Dto\CouponResult;
use App\Services\Sdk\Dto\HeartbeatResult;
use App\Services\Sdk\Dto\UpdateResult;
use App\Services\Sdk\Dto\UsageResult;
use App\Services\Sdk\Dto\ValidationResult;
use App\Services\Sdk\Support\EndpointMapper;
use App\Services\Sdk\Support\ErrorMapper;
use App\Services\Sdk\Support\RequestBuilder;
use App\Services\Sdk\Support\ResponseMapper;
use App\Services\Sdk\Support\RetryPolicy;
use App\Services\Sdk\Support\SdkCache;
use App\Services\Sdk\Support\SdkConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class LicensePlatformClient implements LicensePlatformClientInterface
{
    private SdkConfig $config;

    private RequestBuilder $requestBuilder;

    private SdkCache $cache;

    private ErrorMapper $errorMapper;

    private EndpointMapper $endpointMapper;

    private ResponseMapper $responseMapper;

    public function __construct(array $config = [])
    {
        $this->config = new SdkConfig(
            baseUrl: rtrim((string) ($config['base_url'] ?? ''), '/'),
            apiKey: (string) ($config['api_key'] ?? ''),
            productCode: (string) ($config['product_code'] ?? ''),
            environment: (string) ($config['environment'] ?? 'production'),
            timeout: (int) ($config['timeout'] ?? 10),
            retry: new RetryPolicy(
                maxAttempts: (int) ($config['retry_attempts'] ?? 2),
                delays: $config['retry_delays'] ?? [250, 500]
            ),
            cacheDriver: (string) ($config['cache_driver'] ?? 'file'),
            cachePath: $config['cache_path'] ?? null,
            cacheTtl: (int) ($config['cache_ttl'] ?? 86400),
            logChannel: $config['log_channel'] ?? 'stderr',
        );

        $this->requestBuilder = new RequestBuilder($this->config);
        $this->cache = new SdkCache();
        $this->errorMapper = new ErrorMapper();
        $this->endpointMapper = new EndpointMapper();
        $this->responseMapper = new ResponseMapper();
    }

    public function activate(string $licenseKey, string $domain, array $device): ActivationResult
    {
        return $this->responseMapper->activation($this->post('activate', [
            'license_key' => $licenseKey,
            'domain' => $domain,
            'device' => $device,
        ], $this->requestBuilder->idempotencyHeaders()));
    }

    public function validate(string $licenseKey, string $activationId, string $domain): ValidationResult
    {
        $cacheKey = $this->cacheKey('validate', compact('licenseKey', 'activationId', 'domain'));

        return $this->cache->remember($cacheKey, 300, function () use ($licenseKey, $activationId, $domain): ValidationResult {
            return $this->responseMapper->validation($this->post('validate', [
                'license_key' => $licenseKey,
                'activation_id' => $activationId,
                'domain' => $domain,
            ]));
        });
    }

    public function heartbeat(string $activationId, string $licenseKey, string $domain): HeartbeatResult
    {
        return $this->responseMapper->heartbeat($this->post('heartbeat', [
            'activation_id' => $activationId,
            'license_key' => $licenseKey,
            'domain' => $domain,
        ], $this->requestBuilder->correlationHeaders()));
    }

    public function deactivate(string $activationId, string $licenseKey, string $reason): bool
    {
        $this->post('deactivate', [
            'activation_id' => $activationId,
            'license_key' => $licenseKey,
            'reason' => $reason,
        ]);

        return true;
    }

    public function checkUpdate(string $licenseKey, string $currentVersion, string $domain): UpdateResult
    {
        $cacheKey = $this->cacheKey('checkUpdate', compact('licenseKey', 'currentVersion', 'domain'));

        return $this->cache->remember($cacheKey, 3600, function () use ($licenseKey, $currentVersion, $domain): UpdateResult {
            return $this->responseMapper->update($this->post('checkUpdate', [
                'license_key' => $licenseKey,
                'current_version' => $currentVersion,
                'domain' => $domain,
            ], $this->requestBuilder->correlationHeaders()));
        });
    }

    public function requestOfflineChallenge(string $licenseKey, string $domain, array $device): ChallengeResult
    {
        return $this->responseMapper->challenge($this->post('requestOfflineChallenge', [
            'license_key' => $licenseKey,
            'domain' => $domain,
            'device' => $device,
        ], $this->requestBuilder->idempotencyHeaders()));
    }

    public function confirmOfflineActivation(string $challengeId, string $responseToken): ActivationResult
    {
        return $this->responseMapper->activation($this->post('confirmOfflineActivation', [
            'challenge_id' => $challengeId,
            'response_token' => $responseToken,
        ], $this->requestBuilder->correlationHeaders()));
    }

    public function recordUsage(string $licenseKey, string $metricCode, int $quantity, string $idempotencyKey): UsageResult
    {
        return $this->responseMapper->usage($this->post('recordUsage', [
            'license_key' => $licenseKey,
            'metric_code' => $metricCode,
            'quantity' => $quantity,
            'idempotency_key' => $idempotencyKey,
        ], $this->requestBuilder->idempotencyHeaders($idempotencyKey, [
            'X-Correlation-Id' => $idempotencyKey,
        ])));
    }

    public function validateCoupon(string $couponCode, string $planCode): CouponResult
    {
        $cacheKey = $this->cacheKey('validateCoupon', compact('couponCode', 'planCode'));

        return $this->cache->remember($cacheKey, 3600, function () use ($couponCode, $planCode): CouponResult {
            return $this->responseMapper->coupon($this->post('validateCoupon', [
                'coupon_code' => $couponCode,
                'plan_code' => $planCode,
            ], $this->requestBuilder->correlationHeaders()), $couponCode, $planCode);
        });
    }

    private function post(string $endpoint, array $payload, array $headers = []): array
    {
        $path = $this->endpointMapper->path($endpoint);
        $response = $this->http()
            ->withHeaders($this->requestBuilder->headers($headers))
            ->retry($this->config->retry->maxAttempts, $this->config->retry->laravelRetry())
            ->post($this->config->baseUrl.$path, $payload);

        if ($response->failed()) {
            $this->errorMapper->throw(
                $response->status(),
                $response->json('error_code'),
                $response->json('message'),
                $response->json() ?? []
            );
        }

        return $response->json() ?? [];
    }

    private function http(): PendingRequest
    {
        return Http::timeout($this->config->timeout)
            ->connectTimeout(min(5, $this->config->timeout));
    }

    private function cacheKey(string $endpoint, array $payload): string
    {
        return 'sdk:'.sha1($endpoint.'|'.json_encode($payload));
    }
}
