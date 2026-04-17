<?php

namespace App\Services\Sdk;

use App\Services\Sdk\Exceptions\LicensePlatformException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LicensePlatformClient
{
    private string $baseUrl;

    private ?string $apiKey;

    private ?string $productCode;

    private int $timeout;

    private int $retryAttempts;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->apiKey = $config['api_key'] ?? null;
        $this->productCode = $config['product_code'] ?? null;
        $this->timeout = (int) ($config['timeout'] ?? 10);
        $this->retryAttempts = (int) ($config['retry_attempts'] ?? 3);
    }

    public function activate(string $licenseKey, string $domain, array $device = []): object
    {
        $response = $this->request('post', '/v1/client/licenses/activate', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'domain' => $domain,
            'device' => $device,
        ], ['Idempotency-Key' => (string) Str::uuid()]);

        return $this->mapActivation($response);
    }

    public function validate(string $licenseKey, string $activationId, string $domain): object
    {
        $response = $this->request('post', '/v1/client/licenses/validate', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'activation_id' => $activationId,
            'domain' => $domain,
        ]);

        return $this->mapValidation($response);
    }

    public function heartbeat(string $activationId, string $licenseKey, string $domain): object
    {
        $response = $this->request('post', '/v1/client/licenses/heartbeat', [
            'license_key' => $licenseKey,
            'activation_id' => $activationId,
            'domain' => $domain,
        ], ['X-Correlation-Id' => (string) Str::uuid()]);

        return $this->mapHeartbeat($response);
    }

    public function checkUpdate(string $licenseKey, string $currentVersion, string $domain): object
    {
        $response = $this->request('post', '/v1/client/licenses/update-check', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'current_version' => $currentVersion,
            'domain' => $domain,
        ]);

        return $this->mapUpdateCheck($response);
    }

    public function requestOfflineChallenge(string $licenseKey, string $domain, array $device = []): object
    {
        $response = $this->request('post', '/v1/client/licenses/offline/challenge', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'domain' => $domain,
            'device' => $device,
        ], ['Idempotency-Key' => (string) Str::uuid()]);

        return $this->mapOfflineChallenge($response);
    }

    public function recordUsage(string $licenseKey, string $metric, int $quantity, string $idempotencyKey): object
    {
        $response = $this->request('post', '/v1/client/usage/records', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'metric' => $metric,
            'quantity' => $quantity,
        ], ['Idempotency-Key' => $idempotencyKey]);

        return $this->mapUsage($response);
    }

    public function validateCoupon(string $couponCode, string $planCode): object
    {
        $response = $this->request('post', '/v1/client/coupons/validate', [
            'coupon_code' => $couponCode,
            'plan_code' => $planCode,
        ]);

        return $this->mapCoupon($response);
    }

    private function request(string $method, string $path, array $payload = [], array $headers = []): SdkResponse
    {
        try {
            $request = $this->http()->withHeaders($headers);
            $response = $request->{$method}($this->baseUrl.$path, $payload);

            if ($response->successful()) {
                return SdkResponse::success($response->json('data') ?? []);
            }

            $errorCode = $this->mapErrorCode($response->json('error_code') ?? $response->json('code'));
            $message = $response->json('message') ?? 'Request failed.';

            throw new LicensePlatformException($message, $errorCode, $response->status(), [
                'response' => $response->json(),
            ]);
        } catch (ConnectionException $e) {
            throw new LicensePlatformException($e->getMessage(), 'CONNECTION_ERROR', 0);
        } catch (RequestException $e) {
            throw new LicensePlatformException($e->getMessage(), 'HTTP_ERROR', $e->response?->status() ?? 0);
        }
    }

    private function http(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout(max(1, min($this->timeout, 5)))
            ->retry($this->retryAttempts, 250, throw: false);

        if ($this->apiKey) {
            $request = $request->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        return $request;
    }

    private function mapActivation(SdkResponse $response): object
    {
        $data = $response->data;

        return (object) [
            'activationId' => $data['activation_id'] ?? null,
            'status' => $data['status'] ?? null,
        ];
    }

    private function mapValidation(SdkResponse $response): object
    {
        $data = $response->data;

        return (object) [
            'valid' => (bool) ($data['valid'] ?? false),
            'status' => $data['status'] ?? null,
        ];
    }

    private function mapHeartbeat(SdkResponse $response): object
    {
        $data = $response->data;

        return (object) [
            'accepted' => (bool) ($data['accepted'] ?? false),
            'nextHeartbeatAt' => $data['next_heartbeat_at'] ?? null,
        ];
    }

    private function mapUpdateCheck(SdkResponse $response): object
    {
        $data = $response->data;

        return (object) [
            'updateAvailable' => (bool) ($data['update_available'] ?? false),
        ];
    }

    private function mapOfflineChallenge(SdkResponse $response): object
    {
        $data = $response->data;

        return (object) [
            'challengeId' => $data['challenge_id'] ?? null,
            'expiresAt' => $data['expires_at'] ?? null,
        ];
    }

    private function mapUsage(SdkResponse $response): object
    {
        $data = $response->data;

        return (object) [
            'recorded' => (bool) ($data['recorded'] ?? false),
            'totalUsage' => $data['total_usage'] ?? null,
            'overLimit' => (bool) ($data['over_limit'] ?? false),
        ];
    }

    private function mapCoupon(SdkResponse $response): object
    {
        $data = $response->data;

        return (object) [
            'valid' => (bool) ($data['valid'] ?? false),
            'couponCode' => $data['coupon_code'] ?? null,
            'planCode' => $data['plan_code'] ?? null,
        ];
    }

    private function mapErrorCode(mixed $code): string
    {
        return is_string($code) && $code !== '' ? $code : 'SDK_ERROR';
    }
}
