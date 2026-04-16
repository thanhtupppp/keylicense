<?php

namespace App\Services\Billing;

use App\Models\PlanUsageLimit;
use App\Models\UsageRecord;
use App\Models\UsageSummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UsageService
{
    public function record(string $licenseId, string $planId, string $metric, int $quantity, array $dimensions = [], ?Carbon $recordedAt = null): UsageRecord
    {
        $recordedAt ??= now();

        return DB::transaction(function () use ($licenseId, $planId, $metric, $quantity, $dimensions, $recordedAt): UsageRecord {
            $record = $this->createUsageRecord($licenseId, $planId, $metric, $quantity, $dimensions, $recordedAt);
            $this->rebuildSummary($licenseId, $planId, $metric, $recordedAt);

            return $record;
        });
    }

    public function resolveLimit(string $planId, string $metric): ?PlanUsageLimit
    {
        $cacheKey = $this->limitCacheKey($planId, $metric);

        return Cache::remember($cacheKey, 3600, function () use ($planId, $metric): ?PlanUsageLimit {
            return PlanUsageLimit::query()
                ->where('plan_id', $planId)
                ->where('metric', $metric)
                ->first();
        });
    }

    public function invalidateLimitCache(string $planId, string $metric): void
    {
        Cache::forget($this->limitCacheKey($planId, $metric));
    }

    public function rebuildSummary(string $licenseId, string $planId, string $metric, Carbon $recordedAt): UsageSummary
    {
        [$periodStart, $periodEnd] = $this->periodBounds($recordedAt);
        $totalUsage = $this->sumUsage($licenseId, $metric, $periodStart, $periodEnd);
        $limit = $this->resolveLimit($planId, $metric);
        $limitValue = $limit?->limit_value;
        $usagePercent = $this->usagePercent($totalUsage, $limitValue);

        return UsageSummary::query()->updateOrCreate(
            [
                'license_id' => $licenseId,
                'metric' => $metric,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'plan_id' => $planId,
                'total_usage' => $totalUsage,
                'limit_value' => $limitValue,
                'usage_percent' => $usagePercent,
                'is_over_limit' => $this->isOverLimit($totalUsage, $limitValue),
            ]
        );
    }

    private function createUsageRecord(string $licenseId, string $planId, string $metric, int $quantity, array $dimensions, Carbon $recordedAt): UsageRecord
    {
        return UsageRecord::query()->create([
            'license_id' => $licenseId,
            'plan_id' => $planId,
            'metric' => $metric,
            'quantity' => $quantity,
            'dimensions' => $dimensions,
            'recorded_at' => $recordedAt,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodBounds(Carbon $recordedAt): array
    {
        return [
            $recordedAt->copy()->startOfMonth()->toDateString(),
            $recordedAt->copy()->endOfMonth()->toDateString(),
        ];
    }

    private function sumUsage(string $licenseId, string $metric, string $periodStart, string $periodEnd): int
    {
        return (int) UsageRecord::query()
            ->where('license_id', $licenseId)
            ->where('metric', $metric)
            ->whereDate('recorded_at', '>=', $periodStart)
            ->whereDate('recorded_at', '<=', $periodEnd)
            ->sum('quantity');
    }

    private function usagePercent(int $totalUsage, ?int $limitValue): ?int
    {
        if ($limitValue === null || $limitValue <= 0) {
            return null;
        }

        return (int) floor(($totalUsage / $limitValue) * 100);
    }

    private function isOverLimit(int $totalUsage, ?int $limitValue): bool
    {
        return $limitValue !== null && $totalUsage > $limitValue;
    }

    private function limitCacheKey(string $planId, string $metric): string
    {
        return "plan_usage_limit:{$planId}:{$metric}";
    }
}
