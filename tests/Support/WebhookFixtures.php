<?php

namespace Tests\Support;

use App\Models\WebhookConfig;
use Illuminate\Support\Str;

final class WebhookFixtures
{
    public static function createConfig(string $event = 'license.activated'): WebhookConfig
    {
        return WebhookConfig::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Webhook',
            'event' => $event,
            'target_url' => 'https://example.com/webhook',
            'secret' => 'secret',
            'is_active' => true,
            'retry_count' => 3,
            'timeout_seconds' => 5,
            'metadata' => [],
        ]);
    }
}
