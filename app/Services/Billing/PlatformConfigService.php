<?php

namespace App\Services\Billing;

use App\Models\PlatformConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlatformConfigService
{
    public const CACHE_KEY = 'platform_configs:all';

    public const DEFAULTS = [
        'default_grace_period_days' => [
            'value' => 7,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Grace period mặc định cho activation',
            'is_sensitive' => false,
        ],
        'default_trial_days' => [
            'value' => 14,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Trial duration mặc định',
            'is_sensitive' => false,
        ],
        'default_heartbeat_interval_hours' => [
            'value' => 12,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Heartbeat interval mặc định',
            'is_sensitive' => false,
        ],
        'max_activations_per_license' => [
            'value' => 5,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Hard cap toàn platform',
            'is_sensitive' => false,
        ],
        'email_sender_name' => [
            'value' => 'License Platform',
            'value_type' => PlatformConfig::VALUE_TYPE_STRING,
            'description' => 'Tên hiển thị khi gửi email',
            'is_sensitive' => false,
        ],
        'email_sender_address' => [
            'value' => 'noreply@example.com',
            'value_type' => PlatformConfig::VALUE_TYPE_STRING,
            'description' => 'Email gửi đi',
            'is_sensitive' => true,
        ],
        'support_email' => [
            'value' => 'support@example.com',
            'value_type' => PlatformConfig::VALUE_TYPE_STRING,
            'description' => 'Email support hiển thị trong template',
            'is_sensitive' => true,
        ],
        'platform_name' => [
            'value' => 'License Platform',
            'value_type' => PlatformConfig::VALUE_TYPE_STRING,
            'description' => 'Tên platform trong UI và email',
            'is_sensitive' => false,
        ],
        'platform_url' => [
            'value' => 'https://example.com',
            'value_type' => PlatformConfig::VALUE_TYPE_STRING,
            'description' => 'Base URL của portal',
            'is_sensitive' => false,
        ],
        'trial_abuse_max_per_ip' => [
            'value' => 3,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Max trial per IP trong 24h',
            'is_sensitive' => false,
        ],
        'admin_session_idle_timeout_min' => [
            'value' => 30,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Idle timeout cho admin session',
            'is_sensitive' => false,
        ],
        'admin_max_login_attempts' => [
            'value' => 5,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Max failed login trước khi lock',
            'is_sensitive' => false,
        ],
        'webhook_timeout_seconds' => [
            'value' => 10,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Timeout per webhook delivery',
            'is_sensitive' => false,
        ],
        'webhook_max_retries' => [
            'value' => 5,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Max retry cho webhook',
            'is_sensitive' => false,
        ],
        'bulk_job_max_items' => [
            'value' => 1000,
            'value_type' => PlatformConfig::VALUE_TYPE_INTEGER,
            'description' => 'Max items per bulk job',
            'is_sensitive' => false,
        ],
        'maintenance_mode' => [
            'value' => false,
            'value_type' => PlatformConfig::VALUE_TYPE_BOOLEAN,
            'description' => 'Bật/tắt maintenance mode toàn platform',
            'is_sensitive' => false,
        ],
        'feature_flag_metered_licensing' => [
            'value' => false,
            'value_type' => PlatformConfig::VALUE_TYPE_BOOLEAN,
            'description' => 'Bật/tắt metered licensing feature',
            'is_sensitive' => false,
        ],
        'feature_flag_reseller_portal' => [
            'value' => false,
            'value_type' => PlatformConfig::VALUE_TYPE_BOOLEAN,
            'description' => 'Bật/tắt reseller portal',
            'is_sensitive' => false,
        ],
        'feature_flag_affiliate_program' => [
            'value' => false,
            'value_type' => PlatformConfig::VALUE_TYPE_BOOLEAN,
            'description' => 'Bật/tắt affiliate program',
            'is_sensitive' => false,
        ],
    ];

    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, 600, function (): Collection {
            return PlatformConfig::query()->orderBy('key')->get();
        });
    }

    public function refreshCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function ensureDefaults(): int
    {
        $count = 0;

        foreach (self::DEFAULTS as $key => $definition) {
            PlatformConfig::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $this->normalizeValue($definition['value'], $definition['value_type']),
                    'value_type' => $definition['value_type'],
                    'description' => $definition['description'],
                    'is_sensitive' => $definition['is_sensitive'],
                    'updated_at' => now(),
                ]
            );

            $count++;
        }

        $this->refreshCache();

        return $count;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $config = $this->all()->firstWhere('key', $key);

        return $config?->typed_value ?? $default;
    }

    private function normalizeValue(mixed $value, string $valueType): string
    {
        return match ($valueType) {
            PlatformConfig::VALUE_TYPE_INTEGER => (string) (int) $value,
            PlatformConfig::VALUE_TYPE_BOOLEAN => $value ? 'true' : 'false',
            PlatformConfig::VALUE_TYPE_JSON => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}
