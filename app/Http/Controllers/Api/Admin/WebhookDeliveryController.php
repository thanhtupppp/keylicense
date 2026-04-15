<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class WebhookDeliveryController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'deliveries' => WebhookDelivery::query()->latest('last_attempt_at')->get(),
        ]);
    }
}
