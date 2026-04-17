<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->header('X-Customer-Id')
            ?? $request->attributes->get('customer_id')
            ?? $request->session()->get('customer_id')
            ?? $request->input('customer_id');

        $preferences = NotificationPreference::query()
            ->where('customer_id', $customerId)
            ->orderBy('notification_code')
            ->get();

        return ApiResponse::success([
            'preferences' => $preferences,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $customerId = $request->header('X-Customer-Id')
            ?? $request->input('customer_id')
            ?? $request->attributes->get('customer_id');

        abort_unless($customerId, 422, 'customer_id is required');

        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.notification_code' => ['required', 'string', 'max:128'],
            'preferences.*.channel' => ['required', 'string', 'max:32'],
            'preferences.*.enabled' => ['required', 'boolean'],
        ]);

        foreach ($data['preferences'] as $preference) {
            NotificationPreference::query()->updateOrCreate(
                [
                    'customer_id' => $customerId,
                    'notification_code' => $preference['notification_code'],
                    'channel' => $preference['channel'],
                ],
                [
                    'enabled' => $preference['enabled'],
                    'unsubscribe_token' => NotificationPreference::query()->where([
                        'customer_id' => $customerId,
                        'notification_code' => $preference['notification_code'],
                        'channel' => $preference['channel'],
                    ])->value('unsubscribe_token') ?? Str::random(64),
                ]
            );
        }

        return ApiResponse::success(['updated' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unsubscribe_token' => ['required', 'string'],
        ]);

        $updated = NotificationPreference::query()
            ->where('unsubscribe_token', $data['unsubscribe_token'])
            ->update(['enabled' => false]);

        return ApiResponse::success(['updated' => $updated > 0]);
    }
}
