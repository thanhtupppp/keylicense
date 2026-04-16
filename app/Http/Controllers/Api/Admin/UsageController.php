<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\UsageSummary;
use App\Services\Billing\UsageService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UsageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'plan_id' => ['sometimes', 'uuid', 'exists:plans,id'],
            'license_id' => ['sometimes', 'uuid', 'exists:license_keys,id'],
            'metric' => ['sometimes', 'string', 'max:64'],
        ]);

        $query = UsageSummary::query();
        $this->applyFilters($query, $filters);

        return ApiResponse::success([
            'usage_summaries' => $query->orderByDesc('period_start')->get(),
        ]);
    }

    public function store(Request $request, UsageService $usageService): JsonResponse
    {
        $payload = $request->validate([
            'license_id' => ['required', 'uuid', 'exists:license_keys,id'],
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'metric' => ['required', 'string', 'max:64'],
            'quantity' => ['required', 'integer', 'min:1'],
            'dimensions' => ['sometimes', 'array'],
            'recorded_at' => ['sometimes', 'date'],
        ]);

        $record = $usageService->record(
            $payload['license_id'],
            $payload['plan_id'],
            $payload['metric'],
            $payload['quantity'],
            $payload['dimensions'] ?? [],
            $this->parseRecordedAt($payload),
        );

        return ApiResponse::success([
            'usage_record' => $record,
        ], 201);
    }

    public function limits(Request $request, string $planId): JsonResponse
    {
        Plan::query()->findOrFail($planId);

        return ApiResponse::success([
            'plan_id' => $planId,
            'usage_limits' => $this->loadUsageLimits($planId),
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        if (isset($filters['license_id'])) {
            $query->where('license_id', $filters['license_id']);
        }

        if (isset($filters['metric'])) {
            $query->where('metric', $filters['metric']);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function parseRecordedAt(array $payload): Carbon
    {
        return isset($payload['recorded_at']) ? Carbon::parse($payload['recorded_at']) : now();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadUsageLimits(string $planId): array
    {
        return PlanUsageLimit::query()
            ->where('plan_id', $planId)
            ->orderBy('metric')
            ->get()
            ->map(static fn (PlanUsageLimit $limit) => [
                'id' => $limit->id,
                'plan_id' => $limit->plan_id,
                'metric' => $limit->metric,
                'limit_value' => $limit->limit_value,
                'reset_period' => $limit->reset_period,
                'is_soft_limit' => (bool) $limit->is_soft_limit,
            ])
            ->all();
    }
}
