<?php

namespace App\Services\Billing;

use App\Models\WebhookConfig;
use App\Models\WebhookDelivery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WebhookOutboundService
{
    public function deliver(WebhookConfig $config, string $event, array $payload): WebhookDelivery
    {
        /** @var WebhookDelivery $attempt */
        $attempt = WebhookDelivery::query()->create([
            'webhook_config_id' => $config->id,
            'event' => $event,
            'payload' => $payload,
            'attempt_count' => 1,
            'last_attempt_at' => now(),
            'failed_at' => null,
            'status_code' => null,
            'response_body' => null,
        ]);

        try {
            $response = Http::timeout($config->timeout_seconds ?? 10)
                ->withHeaders([
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Id' => $attempt->id,
                    'X-Webhook-Secret' => $config->secret,
                ])
                ->post($config->target_url, $payload);

            $attempt->forceFill([
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'failed_at' => $response->successful() ? null : now(),
            ])->save();
        } catch (ConnectionException $e) {
            $attempt->forceFill([
                'status_code' => 0,
                'response_body' => $e->getMessage(),
                'failed_at' => now(),
            ])->save();
        }

        return $attempt->fresh();
    }
}
