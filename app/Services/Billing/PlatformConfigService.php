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
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, self::DEFAULTS[$key]['value'] ?? $default);
    }

    public function all(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): Collection {
            return PlatformConfig::query()->get()->keyBy('key')->map(static fn (PlatformConfig $config): mixed => $config->castValue());
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function refreshCache(): void
    {
        $this->flush();
        $this->all();
    }

    public function ensureDefaults(): void
    {
        foreach (self::DEFAULTS as $key => $definition) {
            PlatformConfig::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $definition['value'],
                    'value_type' => $definition['value_type'],
                    'description' => $definition['description'],
                    'is_sensitive' => $definition['is_sensitive'],
                ]
            );
        }

        $this->flush();
    }

    public function environmentRateLimitMultiplier(string $environmentSlug): float
    {
        return match ($environmentSlug) {
            'development' => 0.25,
            'staging' => 0.5,
            'production' => 1.0,
            default => 1.0,
        };
    }

    public function environmentGracePeriodDays(string $environmentSlug): int
    {
        return match ($environmentSlug) {
            'development' => 14,
            'staging' => 10,
            'production' => (int) $this->get('default_grace_period_days', 7),
            default => (int) $this->get('default_grace_period_days', 7),
        };
    }

    public function environmentHeartbeatHours(string $environmentSlug): int
    {
        return match ($environmentSlug) {
            'development' => 6,
            'staging' => 8,
            'production' => (int) $this->get('default_heartbeat_interval_hours', 12),
            default => (int) $this->get('default_heartbeat_interval_hours', 12),
        };
    }
}
