<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookConfig;
use App\Models\WebhookDelivery;
use App\Services\Billing\WebhookOutboundService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookDeliveryController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'deliveries' => WebhookDelivery::query()->latest('last_attempt_at')->get(),
        ]);
    }

    public function store(Request $request, WebhookOutboundService $service): JsonResponse
    {
        $data = $request->validate([
            'webhook_config_id' => ['required', 'uuid'],
            'event' => ['required', 'string', 'max:128'],
            'payload' => ['required', 'array'],
        ]);

        $config = WebhookConfig::query()->findOrFail($data['webhook_config_id']);
        $delivery = $service->deliver($config, $data['event'], $data['payload']);

        return ApiResponse::success([
            'delivery' => $delivery,
        ], 201);
    }
}
