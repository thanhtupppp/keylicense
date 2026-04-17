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

    public function activate(string $licenseKey, string $domain, array $device = []): SdkResponse
    {
        return $this->request('post', '/v1/client/licenses/activate', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'domain' => $domain,
            'device' => $device,
        ], ['Idempotency-Key' => (string) Str::uuid()]);
    }

    public function validate(string $licenseKey, string $activationId, string $domain): SdkResponse
    {
        return $this->request('post', '/v1/client/licenses/validate', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'activation_id' => $activationId,
            'domain' => $domain,
        ]);
    }

    public function heartbeat(string $activationId, string $licenseKey, string $domain): SdkResponse
    {
        return $this->request('post', '/v1/client/licenses/heartbeat', [
            'license_key' => $licenseKey,
            'activation_id' => $activationId,
            'domain' => $domain,
        ], ['X-Correlation-Id' => (string) Str::uuid()]);
    }

    public function updateCheck(string $licenseKey, string $currentVersion, string $domain): SdkResponse
    {
        return $this->request('post', '/v1/client/licenses/update-check', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'current_version' => $currentVersion,
            'domain' => $domain,
        ]);
    }

    public function checkUpdate(string $licenseKey, string $currentVersion, string $domain): SdkResponse
    {
        return $this->updateCheck($licenseKey, $currentVersion, $domain);
    }

    public function deactivate(string $licenseKey, string $activationId, string $domain): SdkResponse
    {
        return $this->request('post', '/v1/client/licenses/deactivate', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'activation_id' => $activationId,
            'domain' => $domain,
        ]);
    }

    public function requestOfflineChallenge(string $licenseKey, string $domain, array $device = []): SdkResponse
    {
        return $this->request('post', '/v1/client/licenses/offline/challenge', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'domain' => $domain,
            'device' => $device,
        ], ['Idempotency-Key' => (string) Str::uuid()]);
    }

    public function recordUsage(string $licenseKey, string $metric, int $quantity, string $idempotencyKey): SdkResponse
    {
        return $this->request('post', '/v1/client/usage/records', [
            'license_key' => $licenseKey,
            'product_code' => $this->productCode,
            'metric' => $metric,
            'quantity' => $quantity,
        ], ['Idempotency-Key' => $idempotencyKey]);
    }

    public function validateCoupon(string $couponCode, string $planCode): SdkResponse
    {
        return $this->request('post', '/v1/client/coupons/validate', [
            'coupon_code' => $couponCode,
            'plan_code' => $planCode,
        ]);
    }

    private function request(string $method, string $path, array $payload = [], array $headers = []): SdkResponse
    {
        try {
            $request = $this->http()->withHeaders($headers);
            $response = $request->{$method}($this->baseUrl.$path, $payload);

            if ($response->successful()) {
                return SdkResponse::success($response->json('data') ?? [], $response->status());
            }

            $errorCode = $this->mapErrorCode($response->json('error_code') ?? $response->json('code'));
            $message = $response->json('message') ?? 'Request failed.';

            return SdkResponse::failure($response->status(), $errorCode, $message);
        } catch (ConnectionException $e) {
            return SdkResponse::failure(0, 'CONNECTION_ERROR', $e->getMessage());
        } catch (RequestException $e) {
            return SdkResponse::failure($e->response?->status() ?? 0, 'HTTP_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            return SdkResponse::failure(0, 'SDK_ERROR', $e->getMessage());
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

    private function mapErrorCode(mixed $code): string
    {
        return is_string($code) && $code !== '' ? $code : 'SDK_ERROR';
    }
}
