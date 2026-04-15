<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class WebhookDeliveryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 15;

    public int $tries = 5;

    public function __construct(
        public string $webhookUrl,
        public string $event,
        public array $payload,
        public string $secret,
        public ?string $deliveryId = null,
    ) {
    }

    public function handle(): void
    {
        $deliveryId = $this->deliveryId ?? (string) str()->uuid();
        $timestamp = (string) now()->timestamp;
        $body = json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $this->secret);

        $response = Http::timeout(10)
            ->withHeaders([
                'X-LP-Signature-256' => $signature,
                'X-LP-Timestamp' => $timestamp,
                'X-LP-Event' => $this->event,
                'X-LP-Delivery' => $deliveryId,
            ])
            ->withBody($body, 'application/json')
            ->post($this->webhookUrl);

        WebhookDelivery::query()->create([
            'webhook_config_id' => null,
            'event' => $this->event,
            'payload' => $this->payload,
            'status_code' => $response->status(),
            'response_body' => $response->body(),
            'attempt_count' => 1,
            'last_attempt_at' => now(),
            'failed_at' => $response->successful() ? null : now(),
        ]);

        if ($response->status() === 410) {
            return;
        }

        if (! $response->successful()) {
            $this->fail(new \RuntimeException('Webhook delivery failed with status '.$response->status()));
        }
    }
}
