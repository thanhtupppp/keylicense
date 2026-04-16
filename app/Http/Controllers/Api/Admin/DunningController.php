<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DunningConfig;
use App\Models\DunningLog;
use App\Models\Subscription;
use App\Support\ApiResponse;
use App\Services\Billing\DunningOrchestrator;
use App\Services\Billing\DunningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DunningController extends Controller
{
    public function report(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'product_id' => ['sometimes', 'nullable', 'uuid'],
            'subscription_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        $from = isset($payload['from']) ? Carbon::parse($payload['from']) : now()->subDays(30);
        $to = isset($payload['to']) ? Carbon::parse($payload['to']) : now();

        $query = DunningLog::query()
            ->with(['subscription.entitlement.plan.product'])
            ->whereBetween('executed_at', [$from, $to]);

        if (! empty($payload['subscription_id'])) {
            $query->where('subscription_id', $payload['subscription_id']);
        }

        if (! empty($payload['product_id'])) {
            $query->whereHas('subscription.entitlement.plan', static fn ($q) => $q->where('product_id', $payload['product_id']));
        }

        $rows = $query->get();

        $report = [
            'from' => $from->toISOString(),
            'to' => $to->toISOString(),
            'total_actions' => $rows->count(),
            'recovered_count' => $rows->where('result', 'recovered')->count(),
            'cancelled_count' => $rows->where('result', 'cancelled')->count(),
            'suspended_count' => $rows->where('result', 'suspended')->count(),
            'recovery_rate_percent' => $rows->count() > 0
                ? round(($rows->where('result', 'recovered')->count() / $rows->count()) * 100, 2)
                : 0.0,
        ];

        $byProduct = $rows
            ->groupBy(static function (DunningLog $item): string {
                return $item->subscription?->entitlement?->plan?->product?->code ?? 'unknown';
            })
            ->map(static function ($items, string $productCode): array {
                $total = $items->count();
                $recovered = $items->where('result', 'recovered')->count();

                return [
                    'product_code' => $productCode,
                    'total_actions' => $total,
                    'recovered_count' => $recovered,
                    'cancelled_count' => $items->where('result', 'cancelled')->count(),
                    'suspended_count' => $items->where('result', 'suspended')->count(),
                    'recovery_rate_percent' => $total > 0 ? round(($recovered / $total) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->all();

        $bySubscription = $rows
            ->groupBy('subscription_id')
            ->map(static function ($items, string $subscriptionId): array {
                $subscription = $items->first()?->subscription;

                return [
                    'subscription_id' => $subscriptionId,
                    'external_id' => $subscription?->external_id,
                    'status' => $subscription?->status,
                    'product_code' => $subscription?->entitlement?->plan?->product?->code,
                    'total_actions' => $items->count(),
                    'steps' => $items->pluck('step')->values()->all(),
                    'latest_action' => $items->sortByDesc('executed_at')->first()?->action,
                    'latest_result' => $items->sortByDesc('executed_at')->first()?->result,
                ];
            })
            ->values()
            ->all();

        return ApiResponse::success([
            'report' => $report,
            'by_product' => $byProduct,
            'by_subscription' => $bySubscription,
        ]);
    }

    public function retryPayment(string $id, DunningService $service, DunningOrchestrator $orchestrator): JsonResponse
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            return ApiResponse::error('SUBSCRIPTION_NOT_FOUND', 'Subscription not found.', 404);
        }

        if ($subscription->status !== 'past_due') {
            return ApiResponse::error('SUBSCRIPTION_NOT_PAST_DUE', 'Subscription is not in past_due state.', 422);
        }

        $orchestrator->retryPayment($subscription);

        return ApiResponse::success([
            'retried' => true,
            'subscription_id' => $subscription->id,
            'status' => 'active',
        ]);
    }

    public function paymentSucceeded(Request $request, string $id, DunningService $service): JsonResponse
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            return ApiResponse::error('SUBSCRIPTION_NOT_FOUND', 'Subscription not found.', 404);
        }

        $service->recoverSubscription($subscription);

        return ApiResponse::success([
            'recovered' => true,
            'subscription_id' => $subscription->id,
            'status' => 'active',
        ]);
    }

    public function configs(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        $query = DunningConfig::query()->orderBy('step');

        if (\array_key_exists('product_id', $payload)) {
            $query->where('product_id', $payload['product_id']);
        }

        $configs = $query
            ->get()
            ->map(static fn (DunningConfig $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'step' => $item->step,
                'days_after_due' => $item->days_after_due,
                'action' => $item->action,
                'email_template_code' => $item->email_template_code,
                'created_at' => $item->created_at?->toISOString(),
            ])
            ->all();

        return ApiResponse::success(['configs' => $configs]);
    }

    public function updateConfigs(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['sometimes', 'nullable', 'uuid'],
            'configs' => ['required', 'array', 'min:1'],
            'configs.*.step' => ['required', 'integer', 'min:1', 'max:10'],
            'configs.*.days_after_due' => ['required', 'integer', 'min:0', 'max:365'],
            'configs.*.action' => ['required', 'string', Rule::in([
                DunningConfig::ACTION_EMAIL,
                DunningConfig::ACTION_SUSPEND,
                DunningConfig::ACTION_CANCEL,
            ])],
            'configs.*.email_template_code' => ['nullable', 'string', 'max:128'],
        ]);

        $productId = $payload['product_id'] ?? null;

        foreach ($payload['configs'] as $item) {
            DunningConfig::query()->updateOrCreate(
                [
                    'product_id' => $productId,
                    'step' => $item['step'],
                ],
                [
                    'days_after_due' => $item['days_after_due'],
                    'action' => $item['action'],
                    'email_template_code' => $item['email_template_code'] ?? null,
                    'created_at' => now(),
                ]
            );
        }

        $configs = DunningConfig::query()
            ->where('product_id', $productId)
            ->orderBy('step')
            ->get()
            ->map(static fn (DunningConfig $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'step' => $item->step,
                'days_after_due' => $item->days_after_due,
                'action' => $item->action,
                'email_template_code' => $item->email_template_code,
                'created_at' => $item->created_at?->toISOString(),
            ])
            ->all();

        return ApiResponse::success(['configs' => $configs]);
    }

    public function logs(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'subscription_id' => ['sometimes', 'nullable', 'uuid'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $query = DunningLog::query()
            ->with(['subscription.entitlement.plan.product'])
            ->orderByDesc('executed_at');

        if (! empty($payload['subscription_id'])) {
            $query->where('subscription_id', $payload['subscription_id']);
        }

        $limit = $payload['limit'] ?? 100;

        $logs = $query
            ->limit($limit)
            ->get()
            ->map(static fn (DunningLog $item): array => [
                'id' => $item->id,
                'subscription_id' => $item->subscription_id,
                'step' => $item->step,
                'action' => $item->action,
                'executed_at' => $item->executed_at?->toISOString(),
                'result' => $item->result,
                'notes' => $item->notes,
                'product_code' => $item->subscription?->entitlement?->plan?->product?->code,
            ])
            ->all();

        return ApiResponse::success(['logs' => $logs]);
    }
}
