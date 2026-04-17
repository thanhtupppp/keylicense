<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerVerificationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        if (! $customer) {
            return ApiResponse::error('UNAUTHORIZED', 'Customer authentication required.', 401);
        }

        return ApiResponse::success([
            'customer' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'email_verified_at' => $customer->email_verified_at?->toISOString(),
                'verification_pending' => filled($customer->verification_token) && blank($customer->email_verified_at),
            ],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'verification_token' => ['required', 'string'],
        ]);

        $customer = Customer::query()
            ->where('email', $payload['email'])
            ->where('verification_token', $payload['verification_token'])
            ->first();

        if (! $customer) {
            return ApiResponse::error('VERIFICATION_INVALID', 'Verification token is invalid.', 422);
        }

        if ($customer->verification_expires_at && now()->greaterThan($customer->verification_expires_at)) {
            return ApiResponse::error('VERIFICATION_EXPIRED', 'Verification token has expired.', 422);
        }

        $customer->forceFill([
            'email_verified_at' => now(),
            'verification_token' => null,
            'verification_expires_at' => null,
        ])->save();

        return ApiResponse::success([
            'verified' => true,
            'customer' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'email_verified_at' => $customer->fresh()->email_verified_at?->toISOString(),
            ],
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $customer = Customer::query()->where('email', $payload['email'])->first();

        if (! $customer) {
            return ApiResponse::error('CUSTOMER_NOT_FOUND', 'Customer not found.', 404);
        }

        $customer->forceFill([
            'verification_token' => Str::random(64),
            'verification_expires_at' => now()->addHours(24),
        ])->save();

        return ApiResponse::success([
            'resent' => true,
            'customer' => [
                'id' => $customer->id,
                'email' => $customer->email,
            ],
        ]);
    }

    private function customer(Request $request): ?Customer
    {
        $customer = $request->attributes->get('customer') ?? $request->user() ?? null;

        return $customer instanceof Customer ? $customer : null;
    }
}
