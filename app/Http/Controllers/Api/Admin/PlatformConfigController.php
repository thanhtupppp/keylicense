<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\PlatformConfig;
use App\Services\Billing\PlatformConfigService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformConfigController extends Controller
{
    public function __construct(private readonly PlatformConfigService $service)
    {
    }

    public function index(): JsonResponse
    {
        $configs = $this->service->all()
            ->map(fn (PlatformConfig $config): array => $this->toResponseItem($config))
            ->all();

        return ApiResponse::success(['configs' => $configs]);
    }

    public function show(string $key): JsonResponse
    {
        $config = PlatformConfig::query()->where('key', $key)->first();

        if (! $config) {
            return ApiResponse::error('PLATFORM_CONFIG_NOT_FOUND', 'Platform config key not found.', 404);
        }

        return ApiResponse::success(['config' => $this->toResponseItem($config)]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'configs' => ['required', 'array', 'min:1'],
            'configs.*.key' => ['required', 'string', 'max:128'],
            'configs.*.value' => ['required'],
            'configs.*.value_type' => ['sometimes', 'string', Rule::in([
                PlatformConfig::VALUE_TYPE_STRING,
                PlatformConfig::VALUE_TYPE_INTEGER,
                PlatformConfig::VALUE_TYPE_BOOLEAN,
                PlatformConfig::VALUE_TYPE_JSON,
            ])],
            'configs.*.description' => ['sometimes', 'nullable', 'string'],
            'configs.*.is_sensitive' => ['sometimes', 'boolean'],
        ]);

        /** @var AdminUser|null $admin */
        $admin = $request->attributes->get('admin_user');

        $updated = [];

        foreach ($payload['configs'] as $item) {
            $existing = PlatformConfig::query()->where('key', $item['key'])->first();
            $valueType = $item['value_type'] ?? $existing?->value_type ?? PlatformConfig::VALUE_TYPE_STRING;

            $value = $this->normalizeValueByType($item['value'], $valueType);

            $config = PlatformConfig::query()->updateOrCreate(
                ['key' => $item['key']],
                [
                    'value' => $value,
                    'value_type' => $valueType,
                    'description' => array_key_exists('description', $item) ? $item['description'] : ($existing?->description),
                    'is_sensitive' => $item['is_sensitive'] ?? $existing?->is_sensitive ?? false,
                    'updated_by' => $admin?->id,
                    'updated_at' => now(),
                ]
            );

            $updated[] = $this->toResponseItem($config);
        }

        $this->service->refreshCache();

        return ApiResponse::success(['configs' => $updated]);
    }

    private function toResponseItem(PlatformConfig $config): array
    {
        return [
            'key' => $config->key,
            'value' => $config->is_sensitive ? '***' : $config->typed_value,
            'value_type' => $config->value_type,
            'description' => $config->description,
            'is_sensitive' => $config->is_sensitive,
            'updated_by' => $config->updated_by,
            'updated_at' => $config->updated_at?->toISOString(),
        ];
    }

    private function normalizeValueByType(mixed $value, string $valueType): string
    {
        return match ($valueType) {
            PlatformConfig::VALUE_TYPE_INTEGER => (string) (int) $value,
            PlatformConfig::VALUE_TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            PlatformConfig::VALUE_TYPE_JSON => is_string($value)
                ? (string) json_encode(json_decode($value, true) ?? [], JSON_UNESCAPED_UNICODE)
                : (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}
